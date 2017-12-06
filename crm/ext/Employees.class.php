<?php



/**
 * Мениджър за информация за служителите
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_Employees extends core_Manager
{
	
	
	/**
     * Заглавие
     */
    public $title = 'Служебни информации';

    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Служебна информация';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'crm_Wrapper,plg_Created,plg_RowTools2';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Лица';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'ceo,planningMaster,crm';


    /**
     * Кой може да създава
     */
    public $canAdd = 'ceo,planningMaster,crm';
    
    
    /**
     * Кой може да изтрива
     */
    public $canDelete = 'no_one';
    
    
    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,mandatory');
        $this->FLD('code', 'varchar', 'caption=Код,mandatory');
        $this->FLD('departments', 'keylist(mvc=planning_Centers,select=name,makeLinks)', 'caption=Центрове');
        
        $this->setDbUnique('personId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('code', self::getDefaultCode($form->rec->personId));
    
    	if(isset($form->rec->id)){
    		$form->setField('departments', 'mandatory');
    	}
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	$rec = $data->form->rec;
    	$data->form->title = core_Detail::getEditTitle('crm_Persons', $rec->personId, $mvc->singleTitle, $rec->id, $mvc->formTitlePreposition);
    }
    
    
    /**
     * Преди запис
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->departments)){
    		$rec->departments = keylist::addKey('', planning_Centers::UNDEFINED_ACTIVITY_CENTER_ID);
    	}
    }
    
    
    /**
     * Какъв е дефолтния код на служителя
     * 
     * @param int $personId
     * @return string
     */
    public static function getDefaultCode($personId)
    {
    	return "ID{$personId}";
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = $form->rec;
    	
    	if($form->isSubmitted()){
    		$rec->code = strtoupper($rec->code);
    			
    		if($personId = $mvc->fetchField(array("#code = '[#1#]' AND #personId != {$rec->personId}", $rec->code), 'personId')){
    			$personLink = crm_Persons::getHyperlink($personId, TRUE);
    			$form->setError($personId, "Номерът е зает от|* {$personLink}");
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->created = "{$row->createdOn} " . tr("от") . " {$row->createdBy}";
    	$row->personId = crm_Persons::getHyperlink($rec->personId, TRUE);
    }
    
    
    /**
     * Подготвя информацията
     *
     * @param stdClass $data
     */
    public function prepareData_(&$data)
    {
    	$rec = self::fetch("#personId = {$data->masterId}");
    	
    	if(!empty($rec)){
    		$data->row = self::recToVerbal($rec);
    		if($this->haveRightFor('edit', $rec->id)){
    			$data->editResourceUrl = array($this, 'edit', $rec->id, 'ret_url' => TRUE);
    		}
    	} else {
    		if($this->haveRightFor('add', (object)array('personId' => $data->masterId))){
    			$data->addExtUrl = array($this, 'add', 'personId' => $data->masterId, 'ret_url' => TRUE);
    		}
    	}
    }
    
    
    /**
     * Рендира информацията
     * 
     * @param stdClass $data
     * @return core_ET $tpl;
     */
    public function renderData($data)
    {
    	 $tpl = getTplFromFile('crm/tpl/HrDetail.shtml');
    	 $tpl->append(tr('Служебен код') . ":", 'resTitle');
    	 
    	 if(isset($data->row)){
    	 	$tpl->placeObject($data->row);
    	 } else {
    	 	$code = "<b>" . tr('Няма') . "</b>";
    	 	$tpl->append($code, 'code');
    	 }
    	 
    	 if($eRec = hr_EmployeeContracts::fetch("#personId = {$data->masterId} AND #state = 'active'")){
    	 	$tpl->append(hr_EmployeeContracts::getHyperlink($eRec->id, TRUE), 'contract');
    	 	$tpl->append(hr_Positions::getHyperlink($eRec->positionId), 'positionId');
    	 }
    	 
    	 if(isset($data->addExtUrl)){
    	 	$link = ht::createLink('', $data->addExtUrl, FALSE, "title=Добавяне на служебни данни,ef_icon=img/16/add.png,style=float:right; height: 16px;");
    	 	$tpl->append($link, 'emBtn');
    	 }
    	 
    	 if(isset($data->editResourceUrl)){
    	 	$link = ht::createLink('', $data->editResourceUrl, FALSE, "title=Редактиране на служебни данни,ef_icon=img/16/edit.png,style=float:right; height: 16px;");
    	 	$tpl->append($link, 'emBtn');
    	 }
    	 
    	 $tpl->removeBlocks();
    	 
    	 return $tpl;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->personId)){
    		if(!crm_Persons::haveRightFor('edit', $rec->personId)){
    			$res = 'no_one';
    		}
    		
    		if($res != 'no_one'){
    			$employeeId = crm_Groups::getIdFromSysId('employees');
    			if(!keylist::isIn($employeeId, crm_Persons::fetchField($rec->personId, 'groupList'))){
    				$res = 'no_one';
    			}
    		}
    	}
    }
    
    
    /**
     * Връща всички служители, които имат код
     * 
     * @param int $centerId   - ид на център на дейност
     * @return array $options - масив със служители
     */
    public static function getEmployeesWithCode($centerId)
    {
    	$options = array();
    	$emplGroupId = crm_Groups::getIdFromSysId('employees');
    	
    	$query = static::getQuery();
    	$query->EXT('groupList', 'crm_Persons', 'externalName=groupList,externalKey=personId');
    	$query->like("groupList", "|{$emplGroupId}|");
    	$query->where("#departments IS NULL OR LOCATE('|{$centerId}|', #departments)");
    	$query->show("personId,code");
    	
    	while($rec = $query->fetch()){
    		$options[$rec->personId] = $rec->code;
    	}
    	
    	return $options;
    }
    
    
    /**
     * Връща кода като линк
     * 
     * @param int $personId
     * @return core_ET $el
     */
    public static function getCodeLink($personId)
    {
    	$el = crm_ext_Employees::fetchField("#personId = {$personId}", 'code');
    	$name = crm_Persons::getVerbal($personId, 'name');
    	 
    	$singleUrl = crm_Persons::getSingleUrlArray($personId);
    	if(count($singleUrl)){
    		$singleUrl['Tab'] = 'PersonsDetails';
    	}
    	 
    	$el = ht::createLink($el, $singleUrl, FALSE, "title=Към визитката на|* '{$name}'");
    	$el = ht::createHint($el, $name, 'img/16/vcard.png', FALSE);
    	
    	return $el;
    }
}