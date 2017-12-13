<?php



/**
 * Мениджър на Оборудвания
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_AssetResources extends core_Master
{
    
	
	/**
     * Заглавие
     */
    public $title = 'Оборудване';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, planning_Wrapper, plg_State2, plg_Search, plg_SaveAndNew,plg_Sorting';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, planningMaster';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, planningMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, planningMaster';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, planning';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Оборудване,code,groupId,folders,quantity=К-во,createdOn,createdBy,state';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Оборудване';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'protocolId';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleAssetResource.shtml';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/equipment.png';
    
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, code, groupId, folders, protocolId';
    
    
    /**
     * Детайли
     */
    public $details = 'planning_AssetResourcesNorms';
    
    
    /**
     * Шаблон (ET) за заглавие
     *
     * @var string
     */
    public $recTitleTpl = '[#name#]<!--ET_BEGIN code--> ([#code#])<!--ET_END code-->';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('name', 'varchar', 'caption=Наименование,mandatory');
    	$this->FLD('groupId', 'key(mvc=planning_AssetGroups,select=name,allowEmpty)', 'caption=Вид,mandatory,silent');
    	$this->FLD('code', 'varchar(16)', 'caption=Код,mandatory');
    	$this->FLD('protocolId', 'key(mvc=accda_Da,select=id)', 'caption=Протокол за пускане в експлоатация,silent,input=hidden');
    	$this->FLD('folders', 'keylist(mvc=doc_Folders,select=title)', 'caption=Папки,mandatory,oldFieldName=departments');
    	$this->FLD('quantity', 'int', 'caption=Kоличество,notNull,value=1');
    	$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
    	$this->FNC('folderId', 'int', 'silent,input=hidden');
    	
    	$this->setDbUnique('code');
    	$this->setDbUnique('protocolId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$rec = $form->rec;
    	
    	// От кое ДМА е оборудването
    	if(isset($rec->protocolId)){
    		$daTitle = accda_Da::fetchField($rec->protocolId, 'title');
    		$form->setDefault('name', $daTitle);
    		$form->info = tr('От') . " " . accda_Da::getHyperLink($rec->protocolId, TRUE);
    	}
    	
    	// Ако има избрана папка по-дефолт скрива се полето за папки
    	if(isset($rec->folderId)){
    		$form->setField('folders', 'input=none');
    	} else {
    		$suggestions = doc_FolderResources::getFolderSuggestions();
    		$form->setSuggestions('folders', array('' => '') + $suggestions);
    		
    		// По дефолт е папката на неопределения център
    		$defFolderId = planning_Centers::getUndefinedFolderId();
    		$form->setDefault('folders', keylist::fromArray(array($defFolderId => $defFolderId)));
    	}
    }
    
    
    /**
     * Преди запис
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->folders)){
    		
    		// Ако няма папки се взима подадената папка или тази на неопределения център
    		$folderId = isset($rec->folderId) ? $rec->folderId : planning_Centers::getUndefinedFolderId();
    		$rec->folders = keylist::addKey('', $folderId);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->groupId = planning_AssetGroups::getHyperlink($rec->groupId, TRUE);
    	$row->created = "{$row->createdOn} " . tr("от") . " {$row->createdBy}";
    	$row->folders = doc_Folders::getVerbalLinks($rec->folders, TRUE);
    	
    	if(isset($fields['-single'])){
    		$row->SingleIcon = ht::createElement("img", array('src' => sbf(str_replace('/16/', '/32/', $mvc->singleIcon), ""), 'alt' => ''));
    		$row->name = self::getRecTitle($rec);
    	}
    	
    	if(isset($rec->protocolId)){
    		$row->protocolId = accda_Da::getHyperlink($rec->protocolId, TRUE);
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'add' && isset($rec)){
    		if(isset($rec->protocolId)){
    			$state = accda_Da::fetchField($rec->protocolId, 'state');
    			if($state != 'active'){
    				$requiredRoles = 'no_one';
    			} else {
    				if($mvc->fetch("#protocolId = {$rec->protocolId}")){
    					$requiredRoles = 'no_one';
    				}
    			}
    		}
    	}
    	
    	// Ако е използван в група, не може да се изтрива
    	if($action == 'delete' && isset($rec->groupId)){
    		if(isset($rec->lastUsedOn)){
    			$requiredRoles = 'no_one';
    		}
    	}
    	
    	// Ако е в група и тя е затворена да не може да се променя
    	if($action == 'changestate' && isset($rec->groupId)){
    		$groupState = planning_AssetGroups::fetchField($rec->groupId, 'state');
    		if($groupState == 'closed'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->showFields = 'search,groupId';
    	$data->listFilter->view = 'horizontal';
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    
    	if($data->listFilter->rec->groupId){
    		$data->query->where("#groupId = {$data->listFilter->rec->groupId}");
    	}
    }
    
    
    /**
     * Избор на наличното оборудване в подадената папка
     * 
     * @param int|NULL $folderId - ид на папка, NULL за всички
     * @return array $res        - налично оборудване
     */
    public static function getByFolderId($folderId = NULL)
    {
    	$query = self::getQuery();
    	$query->where("#state != 'closed'");
    	if(isset($folderId)){
    		$query->where("LOCATE('|{$folderId}|', #folders)");
    	}
    	
    	$res = array();
    	while($rec = $query->fetch()){
    		$res[$rec->id] = self::getRecTitle($rec, FALSE);
    	}
    	
    	return $res;
    }
    
    
    /**
     * Подготовка на детайла
     *
     * @param stdClass $data
     */
    public function prepareDetail_($data)
    {
    	// Подготовка на записите
    	$query = self::getQuery();
    	$query->where("#groupId = {$data->masterId}");
    	$data->recs = $data->rows = array();
    	while ($rec = $query->fetch()){
    		$data->recs[$rec->id] = $rec;
    		$data->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	// Има ли права за добавяне на ново оборудване
    	if($this->haveRightFor('add', (object)array('groupId' => $data->masterId))){
    		$data->addUrl = array($this, 'add', 'groupId' => $data->masterId, 'ret_url' => TRUE);
    	}
    	
    	return $data;
    }
    
    
    /**
     * Рендиране на детайла
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    public function renderDetail_($data)
    {
    	$tpl = new core_ET("");
    	
    	// Рендиране на таблицата с оборудването
    	$data->listFields = arr::make("name=Оборудване,folders=Папки,quantity=К-во,createdOn=Създадено->На,createdBy=Създадено->От,state=Състояние");
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$this->invoke('BeforeRenderListTable', array($tpl, &$data));
    	$tpl->append($table->get($data->rows, $data->listFields));
    	
    	// Бутон за добавяне на ново оборудване
    	if(isset($data->addUrl)){
    		$btn = ht::createBtn('Ново оборудване', $data->addUrl, FALSE, FALSE, "ef_icon={$this->singleIcon},title=Добавяне на ново оборудване към вида");
    		$tpl->replace($btn, 'addAssetBtn');
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Връща нормата на действието за оборудването
     * 
     * @param int $id         - ид на оборудване
     * @param int $productId  - ид на артикул
     * @return stdClass|FALSE - запис на нормата или FALSE ако няма
     */
    public static function getNormRec($id, $productId)
    {
    	if(empty($id)) return FALSE;
    	
    	// Имали конкретна норма за артикула
    	$aNorm = planning_AssetResourcesNorms::fetchNormRec('planning_AssetResources', $id, $productId);
    	if(array_key_exists($productId, $aNorm)) return $aNorm[$productId];
    	
    	// Ако няма се търси нормата зададена в групата му
    	$groupId = self::fetchField($id, 'groupId');
    	$gNorm = planning_AssetResourcesNorms::fetchNormRec('planning_AssetGroups', $groupId, $productId);
    	if(array_key_exists($productId, $gNorm)) return $gNorm[$productId];
    	
    	return FALSE;
    }

    
    /**
     * Извлича общата група на оборудванията
     * 
     * @param mixed $assets       - списък с оборудвания
     * @return int|FALSE $groupId - намерената група или FALSE ако са от различни групи
     */
    public static function getGroupId(&$assets)
    {
    	$assets = is_array($assets) ? $assets : keylist::toArray($assets);
    	if(!planning_AssetGroups::haveSameGroup($assets)) return FALSE;
    	$groupId = planning_AssetResources::fetchField(key($assets), 'groupId');
    	
    	return (!empty($groupId)) ? $groupId : FALSE;
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
    	if($folderId = Request::get('folderId', 'int')){
    		$Cover = doc_Folders::getCover($folderId);
    		$data->form->title = core_Detail::getEditTitle($Cover->className, $Cover->that, $mvc->singleTitle, $data->form->rec->id, $mvc->formTitlePreposition);
    	}
    }
}