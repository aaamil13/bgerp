<?php


/**
 * Модел за "Зони"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Zones extends core_Master
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Зони';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'rack_Wrapper,plg_Sorting,plg_Created,plg_State2,plg_RowTools2';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'admin,ceo,rack';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'admin,ceo,rack';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'admin,ceo,rack';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin,ceo,rack';
    
    
    /**
     * Кой може да генерира нагласяния?
     */
    public $canOrderpickup = 'debug';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo,rack';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'num=Зона,containerId,readiness,state,createdOn,createdBy,pendingHtml=@';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'pendingHtml';
    
    
    /**
     * Детайла, на модела
     */
    public $details = 'rack_ZoneDetails';
    
    
    /**
     * Кой може да селектира документа
     */
    public $canSelectdocument = 'admin,ceo,rack';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    public $singleLayoutFile = 'rack/tpl/SingleLayoutZone.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('num', 'int(max=100)', 'caption=Наименование,mandatory,smartCenter');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Склад,mandatory,remember,input=hidden');
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Документ,input=none');
        $this->FLD('summaryData', 'blob(serialize, compress)', 'input=none');
        $this->FLD('readiness', 'percent', 'caption=Готовност,input=none');
        
        $this->setDbUnique('num,storeId');
        $this->setDbIndex('storeId');
        $this->setDbIndex('containerId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        if (isset($rec->containerId)){
            $row->containerId = doc_Containers::getDocument($rec->containerId)->getLink(0);
        }
        
        $rec->_isSingle = (isset($fields['-single'])) ? true : false;
        $pendingHtml = self::getMovementTable($rec);
        if (!empty($pendingHtml)){ 
            $row->pendingHtml = $pendingHtml;
        }
        
        $row->num = $mvc->getHyperlink($rec->id, true);
    }
    
    
    /**
     * Рендира таблицата със движения към зоната
     * 
     * @param stdClass $rec
     * @return core_ET $tpl
     */
    private function getMovementTable($rec)
    {
        $Movements = clone cls::get('rack_Movements');
        $data = (object)array('recs' => array(), 'rows' => array(), 'listTableMvc' => $Movements);
        $data->listFields = arr::make("movement=От,productId=Артикул,workerId=Товарач", true);
        $data->listTableMvc->setField('position', 'smartCenter');
        $data->recs = self::getCurrentMovementRecs($rec->id);
        
        foreach ($data->recs as $mRec){
            $fields = $Movements->selectFields();
            $fields['-list'] = true;
            $fields['-inline'] = true;
            $data->rows[$mRec->id] = rack_Movements::recToVerbal($mRec, $fields);
        }
        
        // Рендиране на таблицата
        $tpl = new core_ET("");
        if(count($data->rows)){
            $tableClass = ($rec->_isSingle === true) ? 'listTable' : 'simpleTable';
            $showHead = ($rec->_isSingle === true) ? false : true;
            $table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => $tableClass, 'thHide' => $showHead));
            $Movements->invoke('BeforeRenderListTable', array($tpl, &$data));
            
            $tpl->append($table->get($data->rows, $data->listFields));
            $tpl->append("style='width:100%;'", 'TABLE_ATTR');
        }
        
        $tpl->removePendings('COMMON_ROW_ATTR');
       
        return $tpl;
    }
    
    
    /**
     * Връща зоните към подадения склад
     * 
     * @param int|NULL $storeId
     * 
     * @return array $options
     */
    public static function getZones($storeId = null, $onlyFree = false)
    {
        $query = self::getQuery();
        $query->where("#state != 'closed'");
        if ($onlyFree === true) {
            $query->where("#containerId IS NULL");
        }
        if (isset($storeId)){
            $query->where("#storeId = {$storeId}");
        }
        
        $options = array();
        while ($rec = $query->fetch()){
            $options[$rec->id] = self::getRecTitle($rec, false); 
        }
       
        return $options;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $num = self::getVerbal($rec, 'num');
        $title = "Z-{$num}";
        
        if ($escaped) {
            $title = type_Varchar::escape($title);
        }
        
        return $title;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $form->setDefault('storeId', store_Stores::getCurrent('id', $form->rec ? $form->rec->storeId : null));
        
        // Ако има работен запис към зоната не може да се сменя склада
        if (isset($form->rec->containerId)){
            $form->setReadOnly('storeId');
        }
        
        $form->setDefault('num', $mvc->getNextNumber($form->rec->storeId));
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        // По-хубаво заглавие на формата
        $rec = $data->form->rec;
        $data->form->title = core_Detail::getEditTitle('store_Stores', $rec->storeId, 'зона', $rec->id, tr('в склад'));
    }
        
        
    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$storeId}");
        $data->title = 'Зони в склад|* <b style="color:green">' . store_Stores::getHyperlink($storeId, true) . '</b>';
    }
    
    
    /**
     * Избор на зона в документ
     * 
     * @return void|core_ET
     */
    function act_Selectdocument()
    {
        // Проверка на права
        $this->requireRightFor('selectdocument');
        expect($containerId = Request::get('containerId', 'int'));
        expect($document = doc_Containers::getDocument($containerId));
        $this->requireRightFor('selectdocument', (object)array('containerId' => $containerId));
        $documentRec = $document->fetch();
        
        // Подготовка на формата
        $form = cls::get('core_Form');
        $form->title = 'Събиране на редовете на|* ' . $document->getFormTitleLink();
        $form->FLD('zoneId', 'key(mvc=rack_Zones,select=name)', 'caption=Зона');
        $zoneOptions = rack_Zones::getZones($documentRec->{$document->storeFieldName}, true);
        $zoneId = rack_Zones::fetchField("#containerId = {$containerId}", 'id');
        if(!empty($zoneId) && !array_key_exists($zoneId, $zoneOptions)){
            $zoneOptions[$zoneId] = $this->getRecTitle($zoneId);
        }
        $form->setOptions('zoneId', array('' => '') + $zoneOptions);
        $form->setDefault('zoneId', $zoneId);
        $form->input();
        
        // Изпращане на формата
        if($form->isSubmitted()){
            $fRec = $form->rec;
            
            // Присвояване на новата зона
            if(isset($fRec->zoneId)){
                $zoneRec = $this->fetch($fRec->zoneId);
                $zoneRec->containerId = $containerId;
                $this->save($zoneRec);
                
                rack_ZoneDetails::syncWithDoc($zoneRec->id, $containerId);
                $this->updateMaster($zoneRec);
            }
            
            // Старата зона се отчуждава от документа
            if($zoneId != $fRec->zoneId && isset($zoneId)){
                $zoneRec1 = $this->fetch($zoneId);
                $zoneRec1->containerId = NULL;
                $this->save($zoneRec1);
                rack_ZoneDetails::syncWithDoc($zoneRec1->id);
                
                $this->updateMaster($zoneRec1);
            }
            
            // Ако е избрана зона редирект към нея, иначе се остава в документа
            if(isset($fRec->zoneId)){
                redirect(array('rack_Zones', 'single', $fRec->zoneId));
            }
            
            followRetUrl();
        }
        
        // Добавяне на бутони
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/move.png, title = Запис на действието');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        // Записваме, че потребителя е разглеждал този списък
        $document->logInfo('Избор на зона');
        $tpl = $document->getInstance()->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'selectdocument' && isset($rec)){
            if(empty($rec->containerId)){
                $requiredRoles = 'no_one';
            } else {
                $document = doc_Containers::getDocument($rec->containerId);
                $selectedStoreId = store_Stores::getCurrent('id');
                if(!rack_Zones::fetchField("#storeId = {$selectedStoreId} AND #state != 'closed'")){
                    $requiredRoles = 'no_one';
                } else {
                    $documentRec = $document->fetch("state,{$document->storeFieldName}");
                    if(!$document->haveRightFor('single') || !in_array($documentRec->state, array('draft', 'pending')) || $documentRec->{$document->storeFieldName} != $selectedStoreId){
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        if (($action == 'delete' || $action == 'changestate') && isset($rec)){
            if(rack_ZoneDetails::fetch("#zoneId = {$rec->id}") || !empty($rec->containerId)){
                $requiredRoles = 'no_one';
           }
        }
    }
    
    
    /**
     * Изчистване на зоната към която е закачен документа
     * 
     * @param int $containerId
     */
    public static function clearZone($containerId)
    {
        // Към коя зона е в момента закачен документа
        $zoneRec = self::fetch("#containerId = {$containerId}");
        if (empty($zoneRec)) return;
        
        // Затваря движенията към зоната
        rack_Movements::closeByZoneId($zoneRec->id);
        rack_ZoneDetails::delete("#zoneId = {$zoneRec->id}");
        
        $zoneRec->containerId = NULL;
        self::save($zoneRec);
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        $ready = $count = 0;
        
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->where("#zoneId = {$rec->id}");
        while($dRec = $dQuery->fetch()){
            if (!empty($dRec->documentQuantity) && round($dRec->documentQuantity, 4) == round($dRec->movementQuantity, 4)){
                $ready++;
            }
            $count++;
        }
        
        if($count){
            $rec->readiness = $ready / $count;
            $this->save($rec, 'readiness');
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на тулбара в листовия изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        $storeId = store_Stores::getCurrent();
        if ($mvc->haveRightFor('orderpickup', (object)array('storeId' => $storeId))) {
            $data->toolbar->addBtn('Нагласяне', array($mvc, 'orderpickup', 'storeId' => $storeId, 'ret_url' => TRUE), 'ef_icon=img/16/arrow_refresh.png,title=Бързо нагласяне');
       }
    }
    
    
    /**
     * Кои са текущите движения в зоната
     * 
     * @param int $zoneId
     * @return array $res
     */
    public static function getCurrentMovementRecs($zoneId)
    {
        $res = array();
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("LOCATE('|{$zoneId}|', #zoneList) AND #state != 'closed'");
        $mQuery->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 ELSE 3 END)");
        $mQuery->orderBy('orderByState');
        
        while($mRec = $mQuery->fetch()){
            if(!empty($mRec->zones)){
                $zones = type_Table::toArray($mRec->zones);
                $quantity = null;
                foreach ($zones as $zObject) {
                    if($zObject->zone == $zoneId){
                        $quantity = $zObject->quantity;
                        break;
                    }
                }
                
                $clone = clone $mRec;
                $clone->quantity = $quantity;
                $clone->packQuantity = $clone->quantity;
                
                $res[$mRec->id] = $clone;
            }
        }
        
        return $res;
    }
    
    
    /**
     * Следващия номер на зона
     * 
     * @param int $storeId
     * @return double number
     */
    private function getNextNumber($storeId)
    {
        $query = $this->getQuery();
        $query->orderBy('#num', 'DESC');
        $lastRec = $query->fetch("#storeId = {$storeId}");
        
        $num = is_object($lastRec) ? $lastRec->num : 0;
        $num++;
        
        return $num;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        $data->listTableMvc->commonRowClass = 'zonesCommonRow';
    }
    
    
    /**
     * Избор на зона в документ
     *
     * @return void|core_ET
     */
    function act_Orderpickup()
    {
        // Проверка на права
        $this->requireRightFor('orderpickup');
        expect($storeId = Request::get('storeId', 'int'));
        $this->requireRightFor('orderpickup', (object)array('storeId' => $storeId));
        $floor = rack_PositionType::FLOOR;
        
        // Какви са очакваните количества
        $expected = $this->getExpectedProducts($storeId);
        
        // Изчистване на заявките към зоните
        $mQuery = rack_Movements::getQuery();
        $mQuery->where("#state = 'pending'");
        $mQuery->likeKeylist('zoneList', $expected->zones);
        $mQuery->show('id');
        while($mRec = $mQuery->fetch()){
            rack_Movements::delete($mRec->id);
        }
        
        foreach ($expected->products as $pRec){
            
            // Какви са наличните палети за избор
            $pallets = rack_Pallets::getAvailablePallets($pRec->productId, $storeId);
            $floorQuantity = rack_Pallets::getAvailableQuantity(null, $pRec->productId, $storeId);
            if($floorQuantity){
               $pallets[$floor] = (object)array('quantity' => $floorQuantity, 'position' => $floor);
            }
            
            $palletsArr = array();
            foreach ($pallets as $obj){
                $palletsArr[$obj->position] = $obj->quantity;
            }
            
            if(!count($palletsArr)) continue;
           
            // Какво е разпределянето на палетите
            $allocatedPallets = rack_MovementGenerator::mainP2Q($palletsArr, $pRec->zones);
            
            // Ако има генерирани движения се записват
            $movements = rack_MovementGenerator::getMovements($allocatedPallets, $pRec->productId, $pRec->packagingId, $storeId);
            foreach ($movements as $movementRec){
                rack_Movements::save($movementRec);
            }
        }
        
        followRetUrl(null, 'Движенията са генерирани успешно');
    }
    
    
    /**
     * Връща очакваните артикули по зони с документи
     * 
     * @param int $storeId
     * @return stdClass $res
     */
    private function getExpectedProducts($storeId)
    {
        $res = (object)array('products' => array(), 'zones' => array());
        
        $dQuery = rack_ZoneDetails::getQuery();
        $dQuery->EXT('storeId', 'rack_Zones', 'externalName=storeId,externalKey=zoneId');
        $dQuery->where("#documentQuantity IS NOT NULL AND #storeId = {$storeId}");
        
        while($dRec = $dQuery->fetch()){
           
            // Участват само тези по които се очакват още движения
            $needed = $dRec->documentQuantity - $dRec->movementQuantity;
            if(empty($needed) || $needed < 0) continue;
            
            $key = "{$dRec->productId}|{$dRec->packagingId}";
            if(!array_key_exists($key, $res->products)){
                $res->products[$key] = (object)array('productId' => $dRec->productId, 'packagingId' => $dRec->packagingId, 'zones' => array());
                $res->zones[$dRec->zoneId] = $dRec->zoneId;
            }
            
            $res->products[$key]->zones[$dRec->zoneId] += ($dRec->documentQuantity - $dRec->movementQuantity);
        }
        
        return $res;
    }
}