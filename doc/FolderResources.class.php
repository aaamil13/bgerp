<?php



/**
 * Помощен детайл подготвящ и обединяващ заедно ресурсите на центровете на дейност
 *
 * @category  bgerp
 * @package   doc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_FolderResources extends core_Manager
{
	
	
	/**
	 * Единично заглавие
	 */
	public $title = 'Ресурси към папки';
	
	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'Ресурс към папка';
	
	
	/**
	 * Колко да са на страница заданията
	 */
	public $listEmployeesPerPage = 20;
	
	
	/**
	 * Колко да са на страница другите документи
	 */
	public $listAssetsPerPage = 20;
	
	
	/**
	 * Кой може да избира ресурс
	 */
	public $canSelectresource = 'powerUser';
	
	
	/**
	 * Подготвя ресурсите на центъра на дейност
	 */
	public function prepareResources_(&$data)
	{
		$resourceTypes = $data->masterMvc->getResourceTypeArray($data->masterData->rec);
		if(empty($resourceTypes)) return;
		
		$data->TabCaption = 'Ресурси';
		
		$Tab = Request::get('Tab', 'varchar');
		if($Tab != 'Resources') return;
		
		// Подготовка на данните за оборудването
		if(isset($resourceTypes['assets'])){
			$data->aData = clone $data;
			$data->aData->itemsPerPage = $this->listAssetsPerPage;
			$data->aData->listTableMvc = clone cls::get('planning_AssetResources');
			$this->prepareResourceData($data->aData, 'planning_AssetResources');
		}
		
		// Подготовка на данните за служителите
		if(isset($resourceTypes['hr'])){
			$data->eData = clone $data;
			$data->eData->itemsPerPage = $this->listEmployeesPerPage;
			$data->eData->listTableMvc = clone cls::get('planning_Hr');
			$this->prepareResourceData($data->eData, 'planning_Hr');
		}
	}

	
	/**
	 * Подготвя ресурсите
	 * 
	 * @param stdClass $data     - датата
	 * @param string $DetailName - на кой клас
	 */
	private function prepareResourceData(&$data, $DetailName)
	{
		$folderId = $data->masterData->rec->folderId;
		$data->recs = $data->rows = array();
    	$query = $DetailName::getQuery();
    	if($DetailName == 'planning_Hr'){
    		$query->EXT('state', 'crm_Persons', 'externalName=state,externalKey=personId');
    		$query->where("#state != 'rejected' && #state != 'closed'");
    	}
    	$query->where("LOCATE('|{$folderId}|', #folders)");
    	$query->orderBy("state");
    	
    	// Подготовка на пейджъра
    	$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $data->itemsPerPage));
    	$data->Pager->setPageVar($data->masterMvc->className, $data->masterId, $DetailName);
    	$data->Pager->setLimit($query);
    	
    	// Извличане на записите
    	while($dRec = $query->fetch()){
    		$data->recs[$dRec->id] = $dRec;
    		$data->rows[$dRec->id] = $DetailName::recToVerbal($dRec);
    	}
    	
    	// Подготовка на полетата за показване
    	$listFields = ($DetailName == 'planning_Hr') ? "code=Код,personId=Служител,created=Създаване" : "name=Оборудване,code=Код,groupId=Вид,created=Създаване";
    	$data->listFields = arr::make($listFields, TRUE);
    	
    	$type = ($DetailName == 'planning_AssetResources') ? 'asset' : 'employee';
    	if($this->haveRightFor('selectresource', (object)array('folderId' => $folderId, 'type' => $type))){
    		$data->addUrl = array($this, 'selectresource', 'folderId' => $folderId, 'type' => $type, 'ret_url' => TRUE);
    	}
    	
    	if($DetailName == 'planning_AssetResources'){
    		if(planning_AssetResources::haveRightFor('add')){
    			$data->newUrl = array('planning_AssetResources', 'add', 'folderId' => $folderId, 'ret_url' => TRUE);
    		}
    	}
	}
	
	
	/**
	 * Рендиране на ресурсите
	 * 
	 * @param stdClass $data     - датата
	 * @param string $DetailName - на кой клас
	 */
	private function renderResourceData(&$data, $DetailName)
	{
		$Document = cls::get($DetailName);
		$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
		if($DetailName == 'planning_Hr'){
			$tpl->replace("style='margin-top:10px'", 'STYLE');
		} else {
			$hint = ",title=Добавяне на оборудване към " . mb_strtolower($data->masterMvc->singleTitle);
			$hint2 = ",title=Създаване на ново оборудване към " . mb_strtolower($data->masterMvc->singleTitle);
		}
		
		$title = ($DetailName == 'planning_Hr') ? 'Служители' : 'Оборудвания';
		$tpl->append($title, 'title');
		
		if(isset($data->newUrl)){
			$tpl->append(ht::createLink('', $data->newUrl, FALSE, "ef_icon=img/16/add.png{$hint2}"), 'title');
		}
		
		if(isset($data->addUrl)){
			$tpl->append(ht::createLink('', $data->addUrl, FALSE, "ef_icon=img/16/edit.png{$hint}"), 'title');
		}
		
		foreach ($data->listFields as $fldName => $fldCaption){
			if($data->listTableMvc->getField($fldName, FALSE)){
				$data->listTableMvc->setField($fldName, 'tdClass=leftCol');
			}
		}
		
		$Document->invoke('BeforeRenderListTable', array($tpl, &$data));
		$table = cls::get('core_TableView', array('mvc' => $data->listTableMvc));
		$details = $table->get($data->rows, $data->listFields);
		
		$tpl->append($details, 'content');
		if(isset($data->Pager)){
			$tpl->append($data->Pager->getHtml(), 'content');
		}
		
		$tpl->removePlaces();
		$tpl->removeBlocks();
		
		return $tpl;
	}
	
	
	/**
	 * Рендира таблицата с документите
	 * 
	 * @param stdClass $data
	 * @return void|core_ET
	 */
	public function renderResources_(&$data)
	{
		$tpl = new core_ET("");
		
		if(isset($data->aData)){
			$tpl->append($this->renderResourceData($data->aData, 'planning_AssetResources'));
		}
		
		if(isset($data->eData)){
			$tpl->append($this->renderResourceData($data->eData, 'planning_Hr'));
		}
		
		return $tpl;
	}
	
	
	/**
	 * Промяна на оборудванията
	 */
	function act_SelectResource()
	{
		$this->requireRightFor('selectresource');
		expect($folderId = Request::get('folderId', 'int'));
		expect($type = Request::get('type', 'enum(employee,asset)'));
		expect($folderRec = doc_Folders::fetch($folderId));
		$this->requireRightFor('selectresource', (object)array('folderId' => $folderId, 'type' => $type));
		$this->load('planning_Wrapper');
		
		
		$form = cls::get('core_Form');
		$options = $default = array();
		
		// Ако се променят оборудванията
		if($type == 'asset'){
			$this->currentTab = 'Ресурси->Оборудване';
			$typeTitle = 'оборудванията';
			$form->FLD('select', 'keylist(mvc=planning_AssetResources,select=name)', "caption=Оборудване");
			$aQuery = planning_AssetResources::getQuery();
			$aQuery->where("#state != 'closed'");
			while($aRec = $aQuery->fetch()){
				$recTitle = planning_AssetResources::getRecTitle($aRec, FALSE);
				$options[$aRec->id] = $recTitle;
				
				if(keylist::isIn($folderId, $aRec->folders) || is_null($aRec->folders)){
					$default[$aRec->id] = $recTitle;
				}
			}
		} else {
			$this->currentTab = 'Ресурси->Служители';
			
			$typeTitle = 'служителите';
			$form->FLD('select', 'keylist(mvc=crm_Persons,select=name)', "caption=Служители");
			$options = crm_Persons::getEmployeesOptions();
			$dQuery = planning_Hr::getQuery();
			$dQuery->where("LOCATE('|{$folderId}|', #folders)");
			$dQuery->show('personId');
			$default = arr::extractValuesFromArray($dQuery->fetchAll(), 'personId');
		}
		
		// Задаване на полетата от формата
		$form->title = "Промяна на {$typeTitle} към|* " . doc_Folders::getCover($folderId)->getFormTitleLink();;
		$form->setSuggestions('select', $options);
		$form->setDefault('select', keylist::fromArray($default));
		$form->input();
		
		// При събмит на формата
		if($form->isSubmitted()){
			$selected = keylist::toArray($form->rec->select);
			
			// Избраните се обновява департамента им
			foreach ($selected as $id => $name){
				if($type == 'asset'){
					$eRec = planning_AssetResources::fetch($id);
					$eRec->folders = keylist::addKey($eRec->folders, $folderId);
					planning_AssetResources::save($eRec);
				} else {
					if($pRec = planning_Hr::fetch("#personId = {$id}")){
						$pRec->folders = keylist::addKey($pRec->folders, $folderId);
						planning_Hr::save($pRec);
					} else {
						planning_Hr::save((object)array("personId" => $id, 'folders' => keylist::addKey('', $folderId), 'code' => planning_Hr::getDefaultCode($id)));
					}
				}
			}
				
			// Махане на съществуващите
			$removeArr = array_diff_key($default, $selected);
			foreach ($removeArr as $rId => $rName){
				if($type == 'asset'){
					$eRec = planning_AssetResources::fetch($rId);
					$eRec->folders = keylist::removeKey($eRec->folders, $folderId);
					planning_AssetResources::save($eRec);
				} else {
					$eRec = planning_Hr::fetch("#personId = {$rId}");
					$eRec->folders = keylist::removeKey($eRec->folders, $folderId);
					planning_Hr::save($eRec);
				}
			}
			
			followRetUrl(NULL, 'Информацията е обновена успешно');
		}
		
		// Бутони
		$form->toolbar->addSbBtn('Промяна', 'save', 'ef_icon = img/16/disk.png, title = Запис на промените');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
		
		// Записваме, че потребителя е разглеждал този списък
		$this->logInfo("Промяна на ресурсите на центъра на дейност");
		 
		return $this->renderWrapping($form->renderHtml());
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'selectresource' && isset($rec)){
			if(!doc_Folders::haveRightToFolder($rec->folderId, $userId)){
				$requiredRoles = 'no_one';
			} else {
				$Cover = doc_Folders::getCover($rec->folderId);
				if(!$Cover->haveRightFor('edit') && $rec->folderId != planning_Centers::getUndefinedFolderId()){
					$requiredRoles = 'no_one';
				} elseif($rec->type == 'asset'){
					if(!planning_AssetResources::haveRightFor('add')){
						$requiredRoles = 'no_one';
					}
				} elseif($rec->type == 'employee'){
					if(!planning_Hr::haveRightFor('edit')){
						$requiredRoles = 'no_one';
					}
				}
			}
		}
	}
	
	
	/**
	 * Всички папки в които може да се добавя посочения ресурс
	 *
	 * @param string|NULL $forType - 'assets' за оборудване, 'hr' за служители или NULL за всички
	 * @return array $suggestions  - опции за избор на папките
	 */
	public static function getFolderSuggestions($forType = NULL)
	{
		$suggestions = array();
		expect(in_array($forType, array('assets', 'hr', NULL)));
		
		// Папките на центровете на дейност
		$cQuery = planning_Centers::getQuery();
		$cQuery->where("#state != 'rejected' AND #state != 'closed' AND #folderId IS NOT NULL");
		$cQuery->show('folderId');
		$suggestions += arr::extractValuesFromArray($cQuery->fetchAll(), 'folderId');
		
		// Папките на системите, само ако се изисква
		if($forType != 'hr'){
			$sQuery = support_Systems::getQuery();
			$sQuery->where("#state != 'rejected' AND #state != 'closed' AND #folderId IS NOT NULL");
			$sQuery->show('folderId');
			$suggestions += arr::extractValuesFromArray($sQuery->fetchAll(), 'folderId');
		}
		
		// Твърдо забитите папки с ресурси
		$fQuery = planning_FoldersWithResources::getQuery();
		$fQuery->where("#folderId IS NOT NULL");
		if(!is_null($forType)){
			$fQuery->where("LOCATE('{$forType}', #type)");
		}
		
		$fQuery->show('folderId');
		$suggestions += arr::extractValuesFromArray($fQuery->fetchAll(), 'folderId');
		
		// Намиране на имената на папките
		foreach ($suggestions as $key => &$v){
			$v = doc_Folders::getTitleById($key, FALSE);
		}
		
		// Върнатите предложение
		return $suggestions;
	}
}  
    