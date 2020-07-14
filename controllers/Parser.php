<?php namespace Electrica\Parser\Controllers;
/**
 * https://devhints.io/xpath
 * https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/
 * https://github.com/PHPOffice
 * https://github.com/Imangazaliev/DiDOM
 */

ini_set('max_execution_time', '600000');

use Backend\Classes\WidgetBase;
use BackendMenu;
use Backend\Classes\Controller;
use Illuminate\Support\Facades\Input;
use DiDom\Document;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use mysql_xdevapi\Exception;
use October\Rain\Exception\AjaxException;
use October\Rain\Support\Facades\Flash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;

/**
 * Parser Back-end Controller
 */
class Parser extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        require_once dirname(dirname(__FILE__)) . '/lib/vendor/autoload.php';
        parent::__construct();
        BackendMenu::setContext('Electrica.Parser', 'parser', 'parser');
    }

    public function onParserRun(){
        $startMemory = memory_get_usage();
        $startTime = microtime(true);
        $input = Input::get('Parser');

        $data = [];
        $data['file'] = $input['name'];
        //Проверяем, есть ли пагинация
        $out = [];
        if($input['pagination'] && $input['from'] && $input['to']){
            $pagArr = range($input['from'], $input['to']);

            foreach ($pagArr as $page) {
                if(strpos($input['link'], '{$page}')){
                    $pagination = $input['pagination'] . $page;
                    $link = str_replace('{$page}', $pagination, $input['link']);
                }else{
                    $link = $input['link'] . $input['pagination'] . $page;
                }

                $out[] = $this->getDataForUrl($link, $input);
            }
        }else{
            $out[] = $this->getDataForUrl($input['link'], $input);
        }
        foreach ($out as $key => $value){
            foreach ($value as $k => $v) {
                $data['child'][] = $v;
            }
        }

        $file = $this->createXMLDocument($data);
        $time = microtime(true) - $startTime;
        $memory = memory_get_usage() - $startMemory;
        $data['time'] = $time;
        $data['memory'] = $memory;

        $out = [
            'time' => $data['time'],
            'memory' => $data['memory'],
            'file' => $file
        ];

        return [
            '#pesponse' => $this->makePartial('popup', $out)
        ];
    }

    protected function getDataForUrl($link, array $input = []){

        // Проверяем тип Query::TYPE_CSS Query::TYPE_XPATH
        foreach ($input['base_params'] as $key => $val) {
            $type = $val['type'];
            $regexp = $val['regexp'];
            $relative = ($val['relative'] == 1) ? $val['relative_path'] : false;
        }

        $document = new Document($link, true);
        $elements = $document->find($regexp, $type);

        if(count($elements) == 0){
            throw new AjaxException(['X_OCTOBER_ERROR_MESSAGE' => "К сожалению никаких совпадений не найдено"]);
        }
        $i = 0;
        foreach ($elements as $link) {
            if($link && isset($input['params'])){
                if($relative !== false){
                    $link = $relative . $link;
                }
                $data[] = $this->getChildData($link, $input['params']);
            }else{
                throw new AjaxException(['X_OCTOBER_ERROR_MESSAGE' => print_r($elements)]);
            }
            if($i < 3){

            }
            $i++;
        }

        return $data;
    }

    public function getChildData($url ='', $params = []){
        if(!$url){
            throw new AjaxException(['X_OCTOBER_ERROR_MESSAGE' => 'Укажите ссылку!']);
        }
        $document = new Document($url, true);
        $data = [];
        foreach ($params as $key => $val) {
            if($val['regexp']){
                try {
                    $elements = $document->find($val['regexp'], $val['type']);
                }catch (Exception $e){
                    $data['exception'][] = $e->getMessage();
                }
                $i = 1;
                $out = [];
                foreach ($elements as $element) {
                    $data[$val['name']][] = is_object($element) ? $element->text() : $element;

                    if (array_key_exists('subtask', $val)){
                        unset($data[$val['name']]);
                        foreach ($val['subtask'] as $k => $v) {
                            $subs = $element->find($v['regexp'], $v['type']);
                            foreach ($subs as $sub) {
                                $out[$i][] = $sub->text();
                            }
                        }
                        $i++;
                    }
                }
            }
        }
        if($out){
            foreach ($out as $name => $value) {
                $data[$value[0]][] = $value[1];
            }
        }

        return $data;
    }

    public function createXMLDocument($data = []){
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $writer = new Xlsx($spreadsheet);
        $file = 'storage/app/media/' . $data['file'] . '.xlsx';

        if($data['child']){
            $head = [];
            $row = [];
            foreach ($data['child'] as $key => $val){
                foreach ($val as $k => $v) {
                    $head[] = $k;
                }
            }
            $head = array_unique($head);
            $i = 0;
            foreach ($data['child'] as $key => $val){
                foreach ($head as $name){
                    if(array_key_exists($name, $val)){
                        $row[$i][$name] = trim(implode("||\n", $val[$name]));
                    }else{
                        $row[$i][$name] = '';
                    }
                }

                $i++;
            }
        }
        $arrayData[] = $head;
        foreach ($row as $k => $v){
            $arrayData[] = $v;
        }

        $sheet->fromArray($arrayData);
        $writer->save($file);

        return $file;

        //throw new AjaxException(['X_OCTOBER_ERROR_MESSAGE' => "Парсинг прошел успешно. Всего строк: " . count($arrayData)]);
    }
}
