<?php



/**
 * Модел Отчети
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Reports extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Отчети за POS продажби';
    
    
    /**
     * Плъгини за зареждане
     */
   var $loadList = 'pos_Wrapper, plg_Printing, doc_DocumentPlg, acc_plg_DocumentSummary, plg_Search, 
   					bgerp_plg_Blank, doc_ActivatePlg, plg_Sorting';
   
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Отчет за POS продажби";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/report.png';
    

    /**
	 *  Брой елементи на страница 
	 */
    var $listItemsPerPage = "40";
    
    
    /**
     * Брой продажби на страница
     */
    var $listDetailsPerPage = '50';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'pos, ceo, admin';
    
    
	/**
     * Абревиатура
     */
    var $abbr = "Otch";
 
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'pos, ceo, admin';
    
    
    /**
	 * Файл за единичен изглед
	 */
	var $singleLayoutFile = 'pos/tpl/SingleReport.shtml';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title=Заглавие, pointId, cashier, total, paid, change, productCount, state, createdOn, createdBy';
    
    
	/**
     * Групиране на документите
     */
    var $newBtnGroup = "3.5|Търговия";
    
    
    /**
     * 
     */
    var $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=title)', 'caption=Точка, width=9em, mandatory');
    	$this->FLD('cashier', 'user(roles=pos|admin)', 'caption=Касиер, width=9em');
    	$this->FLD('paid', 'double(decimals=2)', 'caption=Сума->Платено, input=none, value=0, summary=amount');
    	$this->FLD('change', 'double(decimals=2)', 'caption=Сума->Ресто, input=none, value=0, summary=amount');
    	$this->FLD('total', 'double(decimals=2)', 'caption=Сума->Продадено, input=none, value=0, summary=amount');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активиран,rejected=Оттеглена)', 'caption=Състояние,input=none,width=8em');
    	$this->FLD('details', 'blob(serialize,compress)', 'caption=Данни,input=none');
    	$this->FLD('productCount', 'int', 'caption=Продукти, input=none, value=0, summary=quantity');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$data->form->setDefault('cashier', core_Users::getCurrent());
    	$data->form->setDefault('pointId', pos_Points::getCurrent());
    	$data->form->setReadOnly('pointId');
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
        $data->query->orderBy('#createdOn', 'DESC');
		$data->listFilter->FNC('user', 'user(roles=pos|admin, allowEmpty)', 'caption=Касиер,width=12em,silent');
		$data->listFilter->FNC('point', 'key(mvc=pos_Points, select=title, allowEmpty)', 'caption=Точка,width=12em,silent');
        $data->listFilter->showFields .= ',user,point';
        
        // Активиране на филтъра
        $data->listFilter->input(NULL, 'silent');
		
		if($filter = $data->listFilter->rec) {
				
				if($filter->user) {
	    			$data->query->where("#cashier = {$filter->user}");
	    		}
	    		
	    		if($filter->point) {
	    			$data->query->where("#pointId = {$filter->point}");
	    		}
	    }
	}
	
	
	/**
	 * Изпълнява се преди вербалното представяне
	 */
	static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
    	// Ако няма записани детайли извличаме актуалните
    	if(!$rec->details){
    		$mvc->extractData($rec);
    	}
    }
	
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	
    	$storeId = pos_Points::fetchField($rec->pointId, 'storeId');
    	$row->storeId = store_Stores::getTitleById($storeId);
    	$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	$row->title = "Отчет за POS продажба №{$rec->id}";
    	
    	if($fields['-list']) {
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, "ef_icon={$mvc->singleIcon}");
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()) {
    		$draftRec = static::fetch("#state='draft' AND #pointId={$form->rec->pointId} AND #cashier={$form->rec->cashier}");
    		if($draftRec) {
    			
    			// Ако има вече чернова с тези параметри директно я отваряме
    			Redirect(array($mvc, 'single', $draftRec->id), FALSE, 'Има вече създадена чернова за посочените касиер и каса');
    		}
    		
    		$reportData = $mvc->fetchData($form->rec->pointId, $form->rec->cashier);
    		
    		// Проверяваме все пак дали има данни за репорта
    		if(!count($reportData->receiptDetails)){
    			$form->setError('cashier, pointId', 'Няма активни бележки');
    			return;
    		}
    	}
    }
    
    
    /**
     * Функция която обновява информацията на репорта
     * извиква се след изпращането на формата и при
     * активация на документа
     * @param stdClass $rec - запис от модела
     */
    private function extractData(&$rec)
    {
    	// Извличаме информацията от бележките
    	$reportData = $this->fetchData($rec->pointId, $rec->cashier);
    	
    	$rec->details = $reportData;
    	$rec->productCount = $rec->change = $rec->total = $rec->paid = 0;
    	if(count($reportData->receiptDetails)){
		    foreach($reportData->receiptDetails as $index => $detail) {
		    	list($action) = explode('|', $index);
		    	
		    	// Изчисляваме общата и платената сума на всички 
		    	($action == 'sale') ? $rec->total += $detail->amount : $rec->paid += $detail->amount;
		    }
   	 	}
   	 	
   	 	foreach($reportData->receipts as $receipt) {
   	 		$rec->productCount += $receipt->products;
   	 	}
   	 	
   	 	$rec->change = $rec->paid - $rec->total;
    }
    
    
    /**
     * Пушваме css и рендираме "детайлите"
     */
    static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	// Рендираме продажбите
    	$tpl->append($mvc->renderListTable($data->rec->details), "SALES");
    	if($data->rec->details->pager){
    		$tpl->append($data->rec->details->pager->getHtml(), "SALE_PAGINATOR");
    	}
    	
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    }
    
    
    /**
     * Обработка детайлите на репорта
     */
    static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$detail = &$data->rec->details;
    	arr::orderA($detail->receiptDetails, 'action');
	    
    	// Табличната информация и пейджъра на плащанията
	    $detail->listFields = "value=Действие, pack=Мярка, quantity=Количество, amount=Сума ({$data->row->baseCurrency})";
    	$detail->rows = $detail->receiptDetails;
    	$mvc->prepareDetail($detail);
	}
    
    
	/**
	 * Инстанциране на пейджъра и модификации по данните спрямо него
	 * @param stdClass $detail - Масив с детайли на отчета (плащания или продажби)
	 */
    function prepareDetail(&$detail)
    {
    	$newRows = array();
    	
    	// Инстанцираме пейджър-а
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    	$Pager->itemsCount = count($detail->rows);
    	$Pager->calc();
    	
    	// Добавяме всеки елемент отговарящ на условието на пейджъра в нов масив
    	if($detail->rows){
    		 $start = $Pager->rangeStart;
    		 $end = $Pager->rangeEnd - 1;
    		 for($i = 0; $i < count($detail->rows); $i++){
    		 	if($i >= $start && $i <= $end){
    		 		$keys = array_keys($detail->rows);
    		 		$newRows[] = $this->getVerbalDetail($keys[$i], $detail->rows[$keys[$i]]);
    		 	}
    		 }
    		 
    		 // Заместваме стария масив с новия филтриран
    		 $detail->rows = $newRows;
    		 
    		 // Добавяме пейджъра
    		 $detail->pager = $Pager;
    	}
    }
    
    
    /**
     * Функция обработваща детайл на репорта във вербален вид
     * @param stdClass $rec-> запис на продажба или плащане
     * @return stdClass $row-> вербалния вид на записа
     */
    private function getVerbalDetail($index, $obj)
    {
    	$row = new stdClass();
    	list($row->action, $row->value, $row->pack) = explode('|', $index);
    	list(, $row->quantity, $row->amount,$row->createdOn) = array_values((array)$obj);
    	
    	$varchar = cls::get("type_Varchar");
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	$row->amount = $double->toVerbal($row->amount); 
    	$currencyCode = acc_Periods::getBaseCurrencyCode($row->createdOn);
    	$double->params['decimals'] = 0;
    	if($row->action == 'sale') {
    		
    		// Ако детайла е продажба
    		$row->ROW_ATTR['class'] = 'report-sale';
    		$info = cat_Products::getProductInfo($row->value, $row->pack);
    		$product = $info->productRec;	
    		if($row->pack){
    			$row->pack = cat_Packagings::getTitleById($row->pack);
    		} else {
    			$row->pack = cat_UoM::getTitleById($product->measureId);
    		}
    		$icon = sbf("img/16/wooden-box.png");
    		$row->value = $product->code . " - " . $product->name;
    		$row->value = ht::createLink($row->value, array("cat_Products", 'single', $row->value), NULL, array('style' => "background-image:url({$icon})", 'class' => 'linkWithIcon'));
    	} else {
    		
    		// Ако детайла е плащане
    		$row->pack = $currencyCode;
    		$value = pos_Payments::getTitleById($row->value);
    		$row->value = tr("Плащания") . ": &nbsp;<i>{$value}</i>";
    		$row->ROW_ATTR['class'] = 'report-payment';
    	}
    	
    	$row->quantity = $double->toVerbal($row->quantity);
    	
    	return $row;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = "Отчет за POS продажба №{$rec->id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;

        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
    /**
     * Подготвя информацията за направените продажби и плащания
     * от всички бележки за даден период от време на даден потребител
     * на дадена точка
     * @param int $pointId - Ид на точката на продажба
     * @param int $userId - Ид на потребител в системата
     * @return array $result - масив с резултати
     * */
    private function fetchData($pointId, $userId)
    {
    	$details = $receipts = array();
    	$query = pos_Receipts::getQuery();
    	$query->where("#pointId = {$pointId}");
    	$query->where("#createdBy = {$userId}");
    	$query->where("#state = 'active'");
    	
    	// извличаме нужната информация за продажбите и плащанията
    	$this->fetchReceiptData($query, $details, $receipts);
    	
    	return (object)array('receipts' => $receipts, 'receiptDetails' => $details);
    }
    
    
    /**
     * Връща продажбите и плащанията направени в търсените бележки групирани
     * @param core_Query $query - Заявка към модела
     * @param array $results - Масив в който ще връщаме резултатите
     * @param array $receipts - Масив от бележките които сме обходили
     */
    private function fetchReceiptData($query, &$results, &$receipts)
    {
    	while($rec = $query->fetch()) {
	    	
    		// запомняме кои бележки сме обиколили
    		$receipts[] = (object)array('id' => $rec->id, 'products' => $rec->productCount);
    		
    		// Добавяме детайлите на бележката
	    	$data = pos_ReceiptDetails::fetchReportData($rec->id);
	    	foreach($data as $index => $obj){
	    		if (!array_key_exists($index, $results)) {
	    			$results[$index] = $obj;
	    		} else {
	    			$results[$index]->quantity += $obj->quantity; 
	    			$results[$index]->amount += $obj->amount; 
	    		}
	    	}
    	}
    }
	
	
	/**
	 * След като документа се активира, обновяваме
	 * данните му и после затваряме всички бележки, които
	 * включва
	 */
	public static function on_Activation($mvc, &$rec)
    {
    	$rRec = $mvc->fetch($rec->id);
    	
    	// Обновяваме информацията в репорта, ако има промени
    	$mvc->extractData($rRec);
    	$mvc->save($rRec);
    	
    	// Всяка бележка в репорта се "затваря"
    	foreach($rRec->details->receipts as $receiptRec){
    		$receiptRec->state = 'closed';
    		pos_Receipts::save($receiptRec);
    	}
    }
    
    
    /**
     * След обработка на ролите
     */
	static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		// Никой неможе да редактира бележка
		if($action == 'activate' && !$rec) {
			$res = 'no_one';
		}
	}
	
	
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
    
        return $folderClass == 'pos_Points';
    }
}