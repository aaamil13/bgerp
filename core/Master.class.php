<?php



/**
 * Клас 'core_Master' - Мениджър за единичните данни на бизнес обекти
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Master extends core_Manager
{

    /**
     * Мениджърите на детайлите записи към обекта
     */
    var $details;
    
    
    /**
     * Титлата на обекта в единичен изглед
     */
    var $singleTitle;
    
    
    /**
     * Изпълнява се след конструирането на мениджъра
     */
    static function on_AfterDescription(core_Master &$mvc)
    {
        $mvc->attachDetails($mvc->details);
    }
    
    
    /**
     * Връща единичния изглед на обекта
     */
    function act_Single()
    {        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Трябва да има id
        expect($id = Request::get('id'));
        
        // Трябва да има $rec за това $id
        if(!($data->rec = $this->fetch($id))) { 
            // Имаме ли въобще права за единичен изглед?
            $this->requireRightFor('single');
            
        }
        
        expect($data->rec);
        
        // Проверяваме дали потребителя може да вижда списък с тези записи
        $this->requireRightFor('single', $data->rec);
        
        // Подготвяме данните за единичния изглед
        $this->prepareSingle($data);
        
        // Рендираме изгледа
        $tpl = $this->renderSingle($data);
        
        // Опаковаме изгледа
        $tpl = $this->renderWrapping($tpl, $data);
        
        // Записваме, че потребителя е разглеждал този списък
        $this->log('Single: ' . ($data->log ? $data->log : tr($data->title)), $id);
        
        return $tpl;
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    function prepareSingle_($data)
    {
        // Подготвяме полетата за показване
        $this->prepareSingleFields($data);
        
        // Подготвяме вербалните стойности на записа
        $data->row = $this->recToVerbal($data->rec, arr::combine($data->singleFields, '-single'));
        
        // Подготвяме титлата
        $this->prepareSingleTitle($data);
        
        // Подготвяме лентата с инструменти
        $this->prepareSingleToolbar($data);
        
        // Подготвяме детайлите
        if(count($this->details)) {
            foreach($this->details as $var => $class) {
                $this->loadSingle($var, $class);
                
                if($var == $class) {
                    $method = 'prepareDetail';
                } else {
                    $method = 'prepare' . $var;
                }
                $detailData = $data->{$var} = new stdClass();
                $detailData->masterMvc = $this;
                $detailData->masterId = $data->rec->id;
                $detailData->masterData = $data;
                $this->{$var}->$method($detailData);
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя списъка с полетата, които ще се показват в единичния изглед
     */
    function prepareSingleFields_($data)
    {
        if(isset($this->singleFields)) {
            
            // Ако са зададени $this->listFields използваме ги тях за колони
            $data->singleFields = arr::make($this->singleFields, TRUE);
        } else {
            
            // Използваме за колони, всички полета, които не са означени с column = 'none'
            $fields = $this->selectFields("#single != 'none'");
            
            if (count($fields)) {
                foreach ($fields as $name => $fld) {
                    $data->singleFields[$name] = $fld->caption;
                }
            }
        }
        
        if (count($data->singleFields)) {
            
            // Ако титлата съвпада с името на полето, вадим името от caption
            foreach ($data->singleFields as $field => $caption) {
                if (($field == $caption) && $this->fields[$field]->caption) {
                    $data->singleFields[$field] = $this->fields[$field]->caption;
                }
            }
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя титлата в единичния изглед
     */
    function prepareSingleTitle_($data)
    {
        $title = $this->getTitleById($data->rec->id);
        
        $data->title = $this->singleTitle . "|* <b style='color:green;'>{$title}</b>";
        
        return $data;
    }
    
    
    /**
     * Подготвя лентата с инструменти за единичния изглед
     */
    function prepareSingleToolbar_($data)
    {
        $data->toolbar = cls::get('core_Toolbar');
        
        $data->toolbar->class = 'SingleToolbar';
        
        if (isset($data->rec->id) && $this->haveRightFor('edit', $data->rec)) {
            $data->toolbar->addBtn('Редакция', array(
                    $this,
                    'edit',
                    $data->rec->id,
                    'ret_url' => TRUE
                ),
                'id=btnEdit,class=btn-edit');
        }
        
        if (isset($data->rec->id) && $this->haveRightFor('delete', $data->rec)) {
            $data->toolbar->addBtn('Изтриване', array(
                    $this,
                    'delete',
                    $data->rec->id,
                    'ret_url' => toUrl(array($this), 'local')
                ),
                'id=btnDelete,class=btn-delete,warning=Наистина ли желаете да изтриете документа?,order=31');
        }
        
        return $data;
    }
    
    
    /**
     * Рендираме общия изглед за 'List'
     */
    function renderSingle_($data, $tpl = NULL)
    {
        // Рендираме общия лейаут
        if(!$tpl) {
            $tpl = $this->renderSingleLayout($data);
        }
        
        // Рендираме заглавието
        $data->row->SingleTitle = $this->renderSingleTitle($data);
        
        // Рендираме лентата с инструменти
        $data->row->SingleToolbar = $this->renderSingleToolbar($data);
        
        // Поставяме данните от реда
        $tpl->placeObject($data->row);
        
        // Поставяме детайлите
        if(count($this->details)) {
            foreach($this->details as $var => $class) {
                
                if($var == $class) {
                    $method = 'renderDetail';
                } else {
                    $method = 'render' . $var;
                }
                
                if($tpl->isPlaceholderExists($var)) {
                    $tpl->replace($this->{$var}->$method($data->{$var}), $var);
                } else {
                    $tpl->append($this->{$var}->$method($data->{$var}), 'DETAILS');
                }
            }
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     */
    function renderSingleLayout_(&$data)
    {
        if(isset($this->singleLayoutFile)) {
            $layoutText = tr('|*' . file_get_contents(getFullPath($this->singleLayoutFile)));
        } elseif(isset($this->singleLayoutTpl)) {
            $layoutText = $this->singleLayoutTpl;
        } else {
            if(count($data->singleFields)) {
                foreach($data->singleFields as $field => $caption) {
                    $fieldsHtml .= "\n<!--ET_BEGIN {$field}--><tr><td>" . tr($caption) . "</td><td>[#{$field}#]</td></tr><!--ET_END {$field}-->";
                }
            }
            
            $class = $this->cssClass ? $this->cssClass : $this->className;
            
            $layoutText = "\n<div style='display:inline-block' class='singleView'>[#SingleToolbar#]<br><div class='{$class}'><h2>[#SingleTitle#]</h2>" .
            "\n<table class='listTable'>{$fieldsHtml}\n</table>\n" .
            "<!--ET_BEGIN DETAILS-->[#DETAILS#]<!--ET_END DETAILS--></div></div>";
        }
        
        if(is_string($layoutText)) {
            $layoutText = tr("|*" . $layoutText);
        }

        return new ET($layoutText);
    }
    
    
    /**
     * Рендира титлата на обекта в single view
     */
    function renderSingleTitle_($data)
    {
        return new ET('[#1#]', tr($data->title));
    }
    
    
    /**
     * Рендира лентата с инструменти на единичния изглед
     */
    function renderSingleToolbar_($data)
    {
        if(cls::isSubclass($data->toolbar, 'core_Toolbar')) {
            
            return $data->toolbar->renderHtml();
        }
    }
    
    
    /**
     * Връща ролите, които могат да изпълняват посоченото действие
     */
    function getRequiredRoles_(&$action, $rec = NULL, $userId = NULL)
    {
        if($action == 'single') {
            if(!($requiredRoles = $this->canSingle)) {
                $requiredRoles = $this->getRequiredRoles('read', $rec, $userId);
            }
        } else { 
            $requiredRoles = parent::getRequiredRoles_($action, $rec, $userId);
        }
        
        return $requiredRoles;
    }
    
    
    /**
     * Прикачане на детайли към този мастър
     * 
     * @param array|string $details
     */
    public function attachDetails($details)
    {
        // Списъка с детайлите става на масив
        $details       = arr::make($details, TRUE);
        $this->details = arr::make($this->details, TRUE);
        
        if (!empty($details)) {
            // Зарежда mvc класовете
            $this->load($details);
            
            foreach($details as $var => $class) {
                $this->details[$var] = $class;
                $this->{$var}->Master = &$this;
            }
        }
    }
}