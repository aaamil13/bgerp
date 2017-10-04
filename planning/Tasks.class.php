<?php



/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Производствени операции
 */
class planning_Tasks extends tasks_Tasks
{
    
    
	/**
	 * Интерфейси
	 */
    public $interfaces = 'label_SequenceIntf=planning_interface_TaskLabel';
    
    
	/**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'planning_DriverIntf';
	
	
	/**
	 * Шаблон за единичен изглед
	 */
	public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
	
	
	/**
	 * Полета от които се генерират ключови думи за търсене (@see plg_Search)
	 */
	public $searchFields = 'title';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'doc_plg_BusinessDoc,doc_plg_Prototype,doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing,plg_RowTools2,bgerp_plg_Blank';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Производствени операции';
	
	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'Производствена операция';
	
	
	/**
	 * Абревиатура
	 */
	public $abbr = 'Pts';
	
	
	/**
	 * Групиране на документите
	 */
	public $newBtnGroup = "3.8|Производство";
	
	
	/**
	 * Клас обграждащ горния таб
	 */
	public $tabTopClass = 'portal planning';
	
	
	/**
	 * Да не се кешира документа
	 */
	public $preventCache = TRUE;
	
	
	/**
	 * Полета, които ще се показват в листов изглед
	 */
	public $listFields = 'expectedTimeStart,title, originId=Задание, progress, folderId,state,modifiedOn,modifiedBy';
	
	
	/**
	 * Дали винаги да се форсира папка, ако не е зададена
	 * 
	 * @see doc_plg_BusinessDoc
	 */
	public $alwaysForceFolderIfEmpty = TRUE;
	
	
	/**
	 * Поле за търсене по потребител
	 */
	public $filterFieldUsers = FALSE;
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,planning,taskWorker';
	
	
	/**
	 * Може ли да се редактират активирани документи
	 */
	public $canEditActivated = TRUE;
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Master &$mvc)
	{
		expect(is_subclass_of($mvc->driverInterface, 'tasks_DriverIntf'), 'Невалиден интерфейс');
		$mvc->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=code,makeLinks)', 'caption=Произвеждане->Оборудване,after=packagingId');
	}
	
	
	/**
	 * Подготовка на формата за добавяне/редактиране
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$rec = &$data->form->rec;
    
		if(isset($rec->systemId)){
			$data->form->setField('prototypeId', 'input=none');
		}
		
		if(empty($rec->id)){
			if($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')){
				unset($rec->threadId);
				$rec->folderId = $folderId;
			}
		}
	}
	
	
	/**
	 * Връща масив със съществуващите задачи
	 * 
	 * @param int $containerId
	 * @param stdClass $data
	 * @return void
	 */
	protected function prepareExistingTaskRows($containerId, &$data)
	{
		// Намираме всички задачи към задание
		$query = $this->getQuery();
		$query->where("#state != 'rejected'");
		
		$query->where("#originId = {$containerId}");
		$query->XPR('orderByState', 'int', "(CASE #state WHEN 'wakeup' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'closed' THEN 4 WHEN 'waiting' THEN 5 ELSE 6 END)");
		$query->orderBy('#orderByState=ASC');
			
		// Подготвяме данните
		while($rec = $query->fetch()){
			if(!cls::load($rec->classId, TRUE)) continue;
			$Class = cls::get($rec->classId);
		
			$data->recs[$rec->id] = $rec;
			$row = $Class->recToVerbal($rec);
			$row->modified = $row->modifiedOn . " " . tr('от||by') . " " . $row->modifiedBy;
			$row->modified = "<div style='text-align:center'> {$row->modified} </div>";
			$data->rows[$rec->id] = $row;
		}
	}
	
	
	/**
	 * Подготвя задачите към заданията
	 */
	public function prepareTasks($data)
	{
		$masterRec = $data->masterData->rec;
		$containerId = $data->masterData->rec->containerId;
		$data->recs = $data->rows = array();
		$this->prepareExistingTaskRows($containerId, $data);
		
		// Ако потребителя може да добавя задача от съответния тип, ще показваме бутон за добавяне
		if($this->haveRightFor('add', (object)array('originId' => $containerId))){
			$data->addUrlArray = array($this, 'add', 'originId' => $containerId, 'ret_url' => TRUE);
		}
		
		// Може ли на артикула да се добавят задачи за производство
		$defDriver = planning_drivers_ProductionTask::getClassId();
		$defaultTasks = cat_Products::getDefaultProductionTasks($data->masterData->rec->productId, $data->masterData->rec->quantity);
		$departments = keylist::toArray($masterRec->departments);
		if(!count($departments) && !count($defaultTasks)){
			$departments = array('' => NULL);
		}
		
		$sysId = (count($defaultTasks)) ? key($defaultTasks) : NULL;
		
		$draftRecs = array();
		foreach ($departments as $depId){
			$depFolderId = isset($depId) ? hr_Departments::forceCoverAndFolder($depId) : NULL;
			if(!doc_Folders::haveRightFor('single', $depFolderId)) continue;
			$r = (object)array('folderId' => $depFolderId, 'title' => cat_Products::getTitleById($masterRec->productId), 'systemId' => $sysId, 'driverClass' => $defDriver);
				
			if(empty($sysId)){
				$r->productId = $masterRec->productId;
			}
				
			$draftRecs[]    = $r;
		}
		
		if(count($defaultTasks)){
			foreach ($defaultTasks as $index => $taskInfo){
		
				// Имали от създадените задачи, такива с този индекс
				$foundObject = array_filter($data->recs, function ($a) use ($index) {
					return $a->systemId == $index;
				});
		
				// Ако има не показваме дефолтната задача
				if(is_array($foundObject) && count($foundObject)) continue;
				$draftRecs[] = (object)array('title' => $taskInfo->title, 'systemId' => $index, 'driverClass' => $taskInfo->driver);
			}
		}
		
		// Вербализираме дефолтните записи
		foreach ($draftRecs as $draft){
			if(!$this->haveRightFor('add', (object)array('originId' => $containerId, 'driverClass' => $draft->driverClass))) continue;
			$url = array('planning_Tasks', 'add', 'folderId' => $draft->folderId, 'originId' => $containerId, 'driverClass' => $draft->driverClass, 'title' => $draft->title, 'ret_url' => TRUE);
			if(isset($draft->systemId)){
				$url['systemId'] = $draft->systemId;
			} else {
				$url['productId'] = $draft->productId;
			}
				
			$row = new stdClass();
			core_RowToolbar::createIfNotExists($row->_rowTools);
			$row->_rowTools->addLink('', $url, array('ef_icon' => 'img/16/add.png', 'title' => "Добавяне на нова задача за производство"));
		
			$row->title = cls::get('type_Varchar')->toVerbal($draft->title);
			$row->ROW_ATTR['style'] .= 'background-color:#f8f8f8;color:#777';
			if(isset($draft->folderId)){
				$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($draft->folderId))->title;
			}
		
			$data->rows[] = $row;
		}
		
		// Бутон за клониране на задачи от задания
		if(planning_Jobs::haveRightFor('cloneTasks', $data->masterId)){
			$data->cloneTaskUrl = array('planning_Jobs', 'cloneTasks', $data->masterId, 'ret_url' => TRUE);
		}
	}
	
	
	/**
	 * Рендира задачите на заданията
	 */
	public function renderTasks($data)
	{
		$tpl = new ET("");
	
		// Ако няма намерени записи, не се рендира нищо
		// Рендираме таблицата с намерените задачи
		$table = cls::get('core_TableView', array('mvc' => $this));
		$fields = 'name=Документ,progress=Прогрес,title=Заглавие,folderId=Папка,expectedTimeStart=Очаквано начало, timeDuration=Продължителност, timeEnd=Край, modified=Модифицирано';
		$data->listFields = core_TableView::filterEmptyColumns($data->rows, $fields, 'timeStart,timeDuration,timeEnd,expectedTimeStart');
		$this->invoke('BeforeRenderListTable', array($tpl, &$data));
		 
		$tpl = $table->get($data->rows, $data->listFields);
		 
		// Имали бутони за добавяне
		if(isset($data->addUrlArray)){
			$btn = ht::createBtn('Производствена операция', $data->addUrlArray, FALSE, FALSE, "title=Създаване на производствена операция към задание,ef_icon={$this->singleIcon}");
			$tpl->append($btn, 'btnTasks');
		}
		
		if(isset($data->cloneTaskUrl)){
			$btn = ht::createBtn('Предишни операции', $data->cloneTaskUrl, FALSE, FALSE, "title=Клониране на производствените операции от старото задание,ef_icon=img/16/clone.png");
			$tpl->append($btn, 'btnTasks');
		}
		
		// Връщаме шаблона
		return $tpl;
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if(isset($rec) && empty($rec->originId)){
			$requiredRoles = 'no_one';
		}
		
		if($action == 'add' && isset($rec->originId)){
			// Може да се добавя само към активно задание
			if($origin = doc_Containers::getDocument($rec->originId)){
				if(!$origin->isInstanceOf('planning_Jobs')){
					$requiredRoles = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * Преди запис на документ
	 */
	protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
	{
		$rec->classId = ($rec->classId) ? $rec->classId : $mvc->getClassId();
		if(!$rec->productId) return;
		
		$productFields = self::getFieldsFromProductDriver($rec->productId);
		$rec->additionalFields = array();
		 
		// Вкарване на записите специфични от драйвера в блоб поле
		if(is_array($productFields)){
			foreach ($productFields as $name => $field){
				if(isset($rec->{$name})){
					$rec->additionalFields[$name] = $rec->{$name};
				}
			}
		}
		 
		$rec->additionalFields = count($rec->additionalFields) ? $rec->additionalFields : NULL;
	}
	
	
	/**
	 * Генерира баркод изображение от даден сериен номер
	 * 
	 * @param string $serial - сериен номер
	 * @return core_ET $img - баркода
	 */
	public static function getBarcodeImg($serial)
	{
		$attr = array();
		
		$conf = core_Packs::getConfig('planning');
		$barcodeType = $conf->PLANNING_TASK_LABEL_COUNTER_BARCODE_TYPE;
		$size = array('width' => $conf->PLANNING_TASK_LABEL_WIDTH, 'height' => $conf->PLANNING_TASK_LABEL_HEIGHT);
		$attr['ratio'] = $conf->PLANNING_TASK_LABEL_RATIO;
		if ($conf->PLANNING_TASK_LABEL_ROTATION == 'yes') {
			$attr['angle'] = 90;
		}
		
		if ($conf->PLANNING_TASK_LABEL_COUNTER_SHOWING == 'barcodeAndStr') {
			$attr['addText'] = array();
		}
		
		// Генериране на баркод от серийния номер, според зададените параметри
		$img = barcode_Generator::getLink($barcodeType, $serial, $size, $attr);
		
		// Връщане на генерираното изображение
		return $img;
	}
	
	
	/**
	 * Информация за произведения артикул по задачата
	 *
	 * @param mixed $id
	 * @return stdClass $arr
	 * 			  o productId       - ид на артикула
	 * 			  o packagingId     - ид на опаковката
	 * 			  o quantityInPack  - количество в опаковка
	 * 			  o plannedQuantity - планирано количество
	 * 			  o wastedQuantity  - бракувано количество
	 * 			  o totalQuantity   - прозведено количество
	 * 			  o storeId         - склад
	 * 			  o fixedAssets     - машини
	 * 			  o indTime         - време за пускане
	 * 			  o startTime       - време за прозиводство
	 */
	public static function getTaskInfo($id)
	{
		$rec = static::fetchRec($id);
		
		$Driver = static::getDriver($rec);
		$info = $Driver->getProductDriverInfo($rec);
		
		return $info;
	}
    
    
	/**
	 * Помощна функция извличаща параметрите на задачата
	 * 
	 * @param stdClass $rec     - запис
	 * @param boolean $verbal   - дали параметрите да са вербални
	 * @return array $params    - масив с обеднението на параметрите на задачата и тези на артикула
	 */
	public static function getTaskProductParams($rec, $verbal = FALSE)
	{
		// Кои са параметрите на артикула
		$classId = planning_Tasks::getClassId();
		$tInfo = planning_Tasks::getTaskInfo($rec);
		$productParams = cat_Products::getParams($tInfo->productId, NULL, TRUE);
		
		// Кои са параметрите на задачата
		$params = array();
		$query = cat_products_Params::getQuery();
		$query->where("#classId = {$classId} AND #productId = {$rec->id}");
		$query->show('paramId,paramValue');
		while($dRec = $query->fetch()){
			$dRec->paramValue = ($verbal === TRUE) ? cat_Params::toVerbal($dRec->paramId, $classId, $rec->id, $dRec->paramValue) : $dRec->paramValue;
			$params[$dRec->paramId] = $dRec->paramValue;
		}
		
		// Обединяване на параметрите на задачата с тези на артикула
		$params = $params + $productParams;
		
		// Връщане на параметрите
		return $params;
	}
    
    
    /**
     * Ф-я връщаща полетата специфични за артикула от драйвера
     *
     * @param int $productId
     * @return array
     */
    public static function getFieldsFromProductDriver($productId)
    {
    	$form = cls::get('core_Form');
    	if($driver = cat_Products::getDriver($productId)){
    		$driver->addTaskFields($productId, $form);
    	}
    	 
    	return $form->selectFields();
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Филтър по всички налични департаменти
    	$departmentOptions = hr_Departments::makeArray4Select('name', "type = 'workshop' AND #state != 'rejected'");
    	
    	if(count($departmentOptions)){
    		$data->listFilter->FLD('departmentId', 'int', 'caption=Звено');
    		$data->listFilter->setOptions('departmentId', array('' => '') + $departmentOptions);
    		$data->listFilter->showFields .= ',departmentId';
    		
    		// Ако потребителя е служител и има само един департамент, той ще е избран по дефолт
    		$cPersonId = crm_Profiles::getProfile(core_Users::getCurrent())->id;
    		$departments = crm_ext_Employees::fetchField("#personId = {$cPersonId}", 'departments');
    		$departments = keylist::toArray($departments);
    		
    		if(count($departments) == 1){
    			$defaultDepartment = key($departments);
    			$data->listFilter->setDefault('departmentId', $defaultDepartment);
    		}
    		
    		$data->listFilter->input('departmentId');
    	}
    	
    	// Добавяне на оборудването към филтъра
    	$fixedAssets = planning_AssetResources::makeArray4Select('name', "#state != 'rejected'");
    	if(count($fixedAssets)){
    		$data->listFilter->FLD('assetId', 'int', 'caption=Оборудване');
    		$data->listFilter->setOptions('assetId', array('' => '') + $fixedAssets);
    		$data->listFilter->showFields .= ',departmentId,assetId';
    		
    		$data->listFilter->input('assetId');
    	}
    	
    	// Филтър по департамент
    	if($departmentFolderId = $data->listFilter->rec->departmentId){
    		$folderId = hr_Departments::fetchField($departmentFolderId, 'folderId');
    		$data->query->where("#folderId = {$folderId}");
    		
    		unset($data->listFields['folderId']);
    	}
    	
    	if($assetId = $data->listFilter->rec->assetId){
    		$data->query->where("LOCATE('|{$assetId}|', #fixedAssets)");
    	}
    }
    
    
    /**
     * Връща масив от задачи към дадено задание
     * 
     * @param int $jobId
     * @return array $res
     */
    public static function getTasksByJob($jobId)
    {
    	$res = array();
    	$oldContainerId = planning_Jobs::fetchField($jobId, 'containerId');
    	$query = static::getQuery();
    	$query->where("#originId = {$oldContainerId} AND #state != 'rejected' AND #state != 'draft'");
    	while($rec = $query->fetch()){
    		$res[$rec->id] = static::getHandle($rec->id);
    	}
    	
    	return $res;
    }
}
