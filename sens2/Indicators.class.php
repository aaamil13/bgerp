<?php


/**
 * Регистър - описание на всички входове и изходи на контролерите
 * Съдържа и текущите им стойности, време за измерване/установяване и евентуално грешки
 *
 * @category  bgerp
 * @package   sens2
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_Indicators extends core_Detail
{
    /**
     * Масив в който се намират всички текущи стойности на индикаторите
     */
    public static $contex;
    
    
    /**
     * Масив с всички задаедени изходи
     */
    public static $outputs;
    
    
    /**
     * Необходими мениджъри
     */
    public $loadList = 'plg_RowTools2, sens2_Wrapper, plg_AlignDecimals, plg_RefreshRows, plg_Rejected, plg_State2';
    
    
    /**
     * Заглавие
     */
    public $title = 'Индикатори на входовете и изходите';
    
    
    /**
     * На колко време ще се актуализира листа
     */
    public $refreshRowsTime = 25000;
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'debug';
    

    /**
     * Права за писане
     */
    public $canEdit = 'debug';


    /**
     * Права за запис
     */
    public $canRead = 'ceo, sens, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, admin, sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, admin, sens';
    
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да променя състоянието на Условията на доставка
     */
    public $canChangestate = 'sens,admin';
    
    public $masterKey = 'controllerId';

    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('controllerId', 'key(mvc=sens2_Controllers, select=name, allowEmpty)', 'caption=Контролер, mandatory, silent,refreshForm');
        $this->FLD('port', 'varchar(64)', 'caption=Порт, mandatory');
        $this->FLD('value', 'double(minDecimals=0, maxDecimals=4)', 'caption=Стойност,input=none');
        $this->FLD('lastValue', 'datetime', 'caption=Към момент,oldFieldName=time,input=none');
        $this->FLD('lastUpdate', 'datetime', 'caption=Последно време на Обновяване,column=none,input=none');
        $this->FLD('error', 'varchar(128)', 'caption=Грешки,input=none');
        $this->FLD('state', 'enum(active=Активен, rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,value=active');
        $this->FLD('uom', 'varchar(16)', 'caption=Мярка,column=none');
        
        $this->FNC('title', 'varchar(64)', 'caption=Заглавие,column=none');
        $this->FNC('isOutput', 'enum(yes,no)', 'caption=Изход ли е?,column=none');
        
        $this->setDbUnique('controllerId,port,uom');
    }
    
    
    /**
     * Изпълнява се при подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        if ($rec->id) {
            $form->setReadOnly('controllerId');
        }
        
        if ($rec->controllerId) {
            $ap = sens2_Controllers::getActivePorts($rec->controllerId);
            foreach ($ap as $port => $pRec) {
                if (!self::fetch(array("#controllerId = {$rec->controllerId} AND #port = '[#1#]'", $port)) || $port == $rec->port) {
                    $opt[$port] = $pRec->caption;
                }
            }
            
            $form->setOptions('port', $opt);
        }
    }
    
    
    /**
     * Дали порта е изходящ
     */
    public static function on_CalcIsOutput($mvc, $rec, $escape = true)
    {
        static $outputs;
        
        $cRec = sens2_Controllers::fetch($rec->controllerId);
        
        if (!$outputs[$cRec->driver]) {
            $drv = cls::get($cRec->driver);
            $outputs[$cRec->driver] = $drv->getOutputPorts();
        }
        
        if ($outputs[$cRec->driver][$rec->port]) {
            $rec->isOutput = 'yes';
        } else {
            $rec->isOutput = 'no';
        }
    }
    
    
    /**
     * Изчислява стойността на функционалното поле 'title'
     */
    public function on_CalcTitle($mvc, $rec)
    {
        $rec->title = $mvc->getRecTitle($rec);
    }
    
    
    public static function getContex()
    {
        if (!self::$contex) {
            $query = self::getQuery();
            while ($iRec = $query->fetch()) {
                self::$contex[$iRec->title] = (double) $iRec->value;
                $controller = self::getVerbal($iRec, 'controllerId');
                self::$contex['$' . $controller . '->' . $iRec->port] = (double) $iRec->value;
            }
        }
        
        return self::$contex;
    }
    
    
    /**
     * Записва текущи данни за вход или изход
     * Ако липсва запис за входа/изхода - създава го
     */
    public static function setValue($controllerId, $port, $value, $time)
    {
        $ap = sens2_Controllers::getActivePorts($controllerId);
        
        $uom = $ap[$port]->uom;
        
        $query = self::getQuery();
        
        while ($r = $query->fetch(array("#controllerId = {$controllerId} AND #port = '[#1#]'", $port))) {
            if ($r->uom != $uom) {
                if ($r->state != 'rejected') {
                    self::reject($r->id);
                }
            } else {
                $rec = $r;
            }
        }
        
        if (!$rec) {
            $rec = new stdClass();
        } else {
            // Ако имаме повторение на последните данни - не правим запис
            if ($rec->value == $value && $rec->lastValue == $time) {
                
                return;
            }
        }
        
        $rec->controllerId = $controllerId;
        $rec->port = $port;
        $rec->value = $value;
        $rec->uom = $uom;
        $rec->lastValue = $time;
        $rec->lastUpdate = $time;
        $rec->state = 'active';
        
        // Ако имаме грешка, поставяме я в правилното място
        $value = trim($value);
        if ($value === '') {
            $value = 'Празна стойност';
        }
        if (!is_numeric($value)) {
            unset($rec->value, $rec->lastValue);
            $rec->error = $value;
        } else {
            $rec->error = '';
        }
        
        
        self::save($rec);
        
        if (!$rec->error) {
            // Записваме и в контекста, ако има такъв
            if (self::$contex) {
                $title = self::getRecTitle($rec);
                self::$contex[$title] = $value;
            }
            
            return $rec->id;
        }
    }
    
    
    /**
     * Връща заглавието на дадения запис (името на порта)
     */
    public static function getRecTitle($rec, $escape = true)
    {
        $cRec = sens2_Controllers::fetch($rec->controllerId);
        
        $title = '$' . sens2_Controllers::getVerbal($cRec, 'name') . '->';
        
        $nameVar = $rec->port . '_name';
        
        if ($cRec->config->{$nameVar}) {
            $title .= $cRec->config->{$nameVar};
        } else {
            $title .= $rec->port;
        }
        
        if ($escape) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->EXT('ctrState', 'sens2_Controllers', 'externalName=state,externalKey=controllerId');
        $data->query->where("#ctrState = 'active'");
        $data->query->orderBy('#controllerId,#port', 'DESC');
    }
    
    
    /**
     * Изпълнява се след подготовката на редовете на листовия изглед
     */
    public static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        if (is_array($data->rows)) {
            foreach ($data->rows as $id => &$row) {
                if (strlen($data->recs[$id]->value)) {
                    $row->value .= "<span class='measure'>" . self::getVerbal($data->recs[$id], 'uom') . '</span>';
                }
            }
        }
    }
    
    
    /**
     * Добавяме означението за съответната мерна величина
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        static $configs = array();
        static $params = array();
        
        if ($rec->lastValue) {
            $color = dt::getColorByTime($rec->lastValue);
            $row->lastValue = ht::createElement('span', array('style' => "color:#{$color}"), $row->lastValue);
        }
        
        if ($rec->error && $rec->lastUpdate) {
            $color = dt::getColorByTime($rec->lastUpdate);
            $row->error = ht::createElement('span', array('style' => "color:#{$color}"), $row->error);
        }
        
        // Определяне на вербалното име на порта
        
        if (!$configs[$rec->controllerId]) {
            $configs[$rec->controllerId] = sens2_Controllers::fetchField($rec->controllerId, 'config');
        }
        
        if (!$params[$rec->controllerId]) {
            $driver = cls::get(sens2_Controllers::fetchField($rec->controllerId, 'driver'));
            $ctrRec = sens2_Controllers::fetch($rec->controllerId);
            $params[$rec->controllerId] = arr::combine($driver->getInputPorts($ctrRec->config), $driver->getOutputPorts($ctrRec->config));
        }
        
        $var = $rec->port . '_name';
        if ($configs[$rec->controllerId]->{$var}) {
            $row->port = type_Varchar::escape($rec->port . ' (' . $configs[$rec->controllerId]->{$var} . ')');
        } elseif(!empty($params[$rec->controllerId][$rec->port]->caption)) {
            $row->port = $rec->port . ' (' . type_Varchar::escape($params[$rec->controllerId][$rec->port]->caption . ')');
        } else {
            $row->port = $rec->port;
        }
        
        $row->controllerId = sens2_Controllers::getLinkToSingle($rec->controllerId, 'name');
        
        if ($rec->isOutput == 'no') {
            $icon = 'property.png';
        } else {
            $icon = 'hand-point.png';
        }
        
        $url = array('sens2_DataLogs', 'List', 'indicatorId' => $rec->id);
        
        $row->port = ht::createLink($row->port, $url, null, "ef_icon=img/16/{$icon}");
    }
}
