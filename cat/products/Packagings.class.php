<?php



/**
 * Клас 'cat_products_Packagings'
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_products_Packagings extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'productId';
    
    
    /**
     * Заглавие
     */
    public $title = 'Опаковки';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Опаковка';
 
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'packagingId=Наименование, quantity=К-во, code=EAN, netWeight=, tareWeight=, weight=Тегло, sizeWidth=, sizeHeight=, sizeDepth=, dimention=Габарити, eanCode=';

    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'cat_Wrapper, plg_RowTools2, plg_SaveAndNew, plg_Created';
    
    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол.
     */
    public $tabName = 'cat_Products';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canAdd = 'ceo,sales,purchase,packEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canEdit = 'ceo,sales,purchase,packEdit';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canDelete = 'ceo,sales,purchase,packEdit';
    

    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  

    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden, silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'tdClass=leftCol,caption=Опаковка,mandatory,smartCenter,removeAndRefreshForm,silent');
        $this->FLD('quantity', 'double(Min=0,smartRound)', 'input,caption=Количество,mandatory,smartCenter');
        $this->FLD('isBase', 'enum(yes=Да,no=Не)', 'caption=Основна,mandatory,maxRadio=2');
        $this->FLD('netWeight', 'cat_type_Weight(min=0)', 'caption=Нето');
        $this->FLD('eanCode', 'gs1_TypeEan', 'caption=EAN');
        $this->FNC('templateId', 'key(mvc=cat_PackParams)', 'caption=Параметри->Шаблон,silent,removeAndRefreshForm=tareWeight|sizeWidth|sizeHeight|sizeDepth,autohide');
        $this->FLD('sizeWidth', 'cat_type_Size(min=0)', 'caption=Параметри->Ширина,autohide');
        $this->FLD('sizeHeight', 'cat_type_Size(min=0)', 'caption=Параметри->Височина,autohide');
        $this->FLD('sizeDepth', 'cat_type_Size(min=0)', 'caption=Параметри->Дълбочина,autohide');
        $this->FLD('tareWeight', 'cat_type_Weight(min=0)', 'caption=Параметри->Тара,autohide');
        
        $this->setDbUnique('productId,packagingId');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        if ($form->isSubmitted()){
            $rec = &$form->rec;
            
            $baseMeasureId = cat_Products::getProductInfo($rec->productId)->productRec->measureId;
            if($baseMeasureId == $rec->packagingId){
                if($rec->quantity != 1){
                    $form->setError('quantity', 'Количеството не може да е различно от единица за избраната мярка/опаковка');
                }
            }
            
            if($rec->eanCode) {
                    
                // Проверяваме дали има продукт с такъв код (като изключим текущия)
                $check = $mvc->Master->getByCode($rec->eanCode);
                if($check && ($check->productId != $rec->productId)
                    || ($check->productId == $rec->productId && $check->packagingId != $rec->packagingId)) {
                    $form->setError('eanCode', 'Има вече продукт с такъв код!');
                }
            }
                
            // Ако за този продукт има друга основна опаковка, тя става не основна
            if($rec->isBase == 'yes' && $packRec = static::fetch("#productId = {$rec->productId} AND #isBase = 'yes'")){
                $packRec->isBase = 'no';
                static::save($packRec, 'isBase');
            }
            
            // Проверка на к-то
            if(!deals_Helper::checkQuantity($baseMeasureId, $rec->quantity, $warning)){
                $form->setError('quantity', $warning);
            }
            
            if(!$form->gotErrors()){
            	$kgId = cat_UoM::fetchBySinonim('kg')->id;
            	$kgDerivitives = cat_UoM::getSameTypeMeasures($kgId);
            	if(array_key_exists($rec->packagingId, $kgDerivitives)){
            		 
            		// Ако опаковката/мярката е от групата на килограм, то теглото може да се изчисли
            		$rec->netWeight = cat_UoM::convertToBaseUnit($rec->quantity, $rec->packagingId);
            	} else {
            		 
            		// Ако опаковката/мярката не е от групата на килограм, но има опаковка килограм,
            		// то теглото може да се изчисли
            		$packRec = self::getPack($rec->productId, $kgId);
            		if(!empty($packRec)){
            			$rec->netWeight = $rec->quantity / $packRec->quantity;
            		}
            	}
            }
        }
    }
    
    
    /**
     * След проверка на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($requiredRoles == 'no_one') return;
        
        if($action == 'add' && isset($rec->productId)) {
            if (!count($mvc::getRemainingOptions($rec->productId))) {
                $requiredRoles = 'no_one';
            } 
        }
        
        if(($action == 'add' ||  $action == 'delete' ||  $action == 'edit') && isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'isPublic,state');
            if($productRec->state != 'active' && $productRec->state != 'template'){
                $requiredRoles = 'no_one';
            } elseif($productRec->isPublic == 'yes'){
                if(!haveRole('ceo,packEdit')){
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        // Ако потрбителя няма достъп до сингъла на артикула, не може да модифицира опаковките
        if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec) && $requiredRoles != 'no_one'){
            $productInfo = cat_Products::getProductInfo($rec->productId);
            if(empty($productInfo->meta['canStore'])){
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако опаковката вече е използвана не може да се изтрива
        if($action == 'delete' && isset($rec)){
            if(self::isUsed($rec->productId, $rec->packagingId, strtolower(Request::get('Act')) == 'list')){
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако потребителя не е създал записа, трябва да има cat или ceo за да го промени
        if(($action == 'edit' || $action == 'delete') && isset($rec)){
            if($rec->createdBy != $userId && !haveRole('ceo,packEdit', $userId)){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране.
     *
     * @param core_Manager $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
        if(!(count($mvc::getRemainingOptions($data->form->rec->productId)) - 1)){
            $data->form->toolbar->removeBtn('saveAndNew');
        }
    }
    
    
    /**
     * Връща не-използваните параметри за конкретния продукт, като опции
     *
     * @param $productId int ид на продукта
     * @param $id int ид от текущия модел, което не трябва да бъде изключено
     * @return array $options - опциите
     */
    public static function getRemainingOptions($productId, $id = NULL)
    {
        // Извличаме мерките и опаковките
        $uomArr = cat_UoM::getUomOptions();
        $packArr = cat_UoM::getPackagingOptions();
        
        // Отсяваме тези, които вече са избрани за артикула
        $query = self::getQuery();
        if($id) {
            $query->where("#id != {$id}");
        }

        while($rec = $query->fetch("#productId = $productId")) {
           unset($uomArr[$rec->packagingId]);
           unset($packArr[$rec->packagingId]);
        }

        // Групираме опциите, ако има такива
        $options = array();
        if(count($packArr)){
            $options = array('p' => (object)array('group' => TRUE, 'title' => tr('Опаковки'))) + $packArr;
        }
        
        if(count($uomArr)){
            $options += array('u' => (object)array('group' => TRUE, 'title' => tr('Мерки'))) + $uomArr;
        }
        
        // Връщаме намерените опции
        return $options;
    }

    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    protected static function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        $options = self::getRemainingOptions($rec->productId, $rec->id);
		$form->setOptions('packagingId', array('' => '') + $options);
		
		if(isset($rec->packagingId)){
			
			// Намиране на наличните шаблони
			$packTemplateOptions = cat_PackParams::getPackaginTemplates($rec->packagingId);
			if(count($packTemplateOptions)){
				$form->setField('templateId', 'input');
				$form->setOptions('templateId', array('' => '') + $packTemplateOptions);
		
				// Зареждане на дефолтите от шаблоните
				if(isset($rec->templateId)){
					$pRec = cat_PackParams::fetch($rec->templateId);
					$form->setDefault('sizeWidth', $pRec->sizeWidth);
					$form->setDefault('sizeHeight', $pRec->sizeHeight);
					$form->setDefault('sizeDepth', $pRec->sizeDepth);
					$form->setDefault('tareWeight', $pRec->tareWeight);
				}
			}
		}
		
		// Ако има дефолтни опаковки от драйвера
        if($Driver = cat_Products::getDriver($rec->productId)){
        	$defaults = $Driver->getDefaultPackagings($rec);
        	
        	if(count($defaults)){
        		foreach ($defaults as $def){
        			
        			// Ако опаковката още не е зададена
        			if(isset($options[$def->packagingId])){
        				$form->setDefault('packagingId', $def->packagingId);
        				
        				// За дава се избрана по дефолт
        				foreach (array('quantity', 'isBase', 'tareWeight', 'sizeWidth', 'sizeHeight', 'sizeDepth') as $fld){
        					
        					if($def->justGuess === TRUE){
        						$form->setDefault($fld, $def->{$fld});
        					} else {
        						
        						// Ако не е задължителна само стойностите и се подават като плейсходлъри
        						$placeholder = $mvc->getFieldType($fld)->toVerbal($def->{$fld});
        						$placeholder = explode(' ', $placeholder);
        						$placeholder = $placeholder[0];
        						$form->setField($fld, "placeholder={$placeholder}");
        					}
        				}
        				
        				break;
        			}
        		}
        	}
        }
        
        if(isset($rec->packagingId)){
        	$kgId = cat_UoM::fetchBySinonim('kg')->id;
        	$kgDerivitives = cat_UoM::getSameTypeMeasures($kgId);
        	if(array_key_exists($rec->packagingId, $kgDerivitives)){
        		$form->setField('netWeight', 'input=none');
        	}
        }
        
        $form->setDefault('isBase', 'no');
        
        $pInfo = cat_Products::getProductInfo($rec->productId);
        $unit = cat_UoM::getShortName($pInfo->productRec->measureId);
        $form->setField('quantity', "unit={$unit}");
        
        // Ако редактираме, но опаковката е използвана не може да се променя
        if(isset($rec->id)){
            if(self::isUsed($rec->productId, $rec->packagingId, TRUE)){
                $form->setReadOnly('packagingId');
                $form->setReadOnly('quantity');
            }
        }
        
        if($kgPack = self::getPack($rec->productId, $kgId)){
        	if($kgPack != $rec->id){
        		
        	}
        }
    }
    
   
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        foreach (array('sizeWidth', 'sizeHeight', 'sizeDepth') as $sizeFld){
        	if($rec->{$sizeFld} == 0) {
        		$row->{$sizeFld} = '-';
        	}
        }
        
        $row->dimention = "{$row->sizeWidth} x {$row->sizeHeight} x {$row->sizeDepth}";
        
        if(!empty($rec->eanCode)){
            $row->code = $row->eanCode;
        }
        
        if(!empty($rec->netWeight)){
            $row->weight = tr("|Нето|*: ") . $row->netWeight . "<br>";
        }
        
        if(!empty($rec->tareWeight)){
            $row->weight .= tr("|Тара|*: {$row->tareWeight}");
        }
        
        if($rec->isBase == 'yes'){
            $row->packagingId = "<b>" . $row->packagingId . "</b>";
        }
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public function preparePackagings($data)
    {
    	$masterRec = is_object($data->masterData->rec) ? $data->masterData->rec : $data->masterMvc->fetch($data->masterId);
    	if($masterRec->canStore == 'no'){
            $data->notStorable = TRUE;
        }
        
        $data->recs = $data->rows = array();
        
        $query = self::getQuery();
        $query->where("#productId = {$data->masterId}");
        $query->orderBy('quantity', 'ASC');
        $query->orderBy('packagingId', 'ASC');
        while($rec = $query->fetch()){
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = self::recToVerbal($rec);
        }
        
        if ($this->haveRightFor('add', (object)array('productId' => $data->masterId))) {
            $data->addUrl = array($this, 'add', 'productId' => $data->masterId, 'ret_url' => getCurrentUrl() + array('#'=> get_class($this)));
        }
        
        $data->listFields = arr::make($this->listFields, TRUE);
        $shortMeasure = cat_UoM::getShortName($masterRec->measureId);
        $data->listFields['quantity'] .= "|* <span class='small'>( {$shortMeasure} )</span>";
    }
    
    
    /**
     * Подготвя опаковките на артикула
     * 
     * @param stdClass $data
     */
    public function renderPackagings($data)
    {
        if($data->notStorable === TRUE && !count($data->recs)) return;
        $tpl = (isset($data->tpl)) ? $data->tpl : getTplFromFile('cat/tpl/PackigingDetail.shtml');
        
        if ($data->addUrl  && !Mode::isReadOnly()) {
            $addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: middle; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нова опаковка/мярка');
            $tpl->append($addBtn, 'TITLE');
        }
        
        // Ако артикула не е производим, показваме в детайла
        if($data->notStorable === TRUE){
            $tpl->append(" <small style='color:red'>(" . tr('Артикулът не е складируем') . ")</small>", 'TITLE');
            $tpl->append("state-rejected", 'TAB_STATE');
            unset($data->listFields['tools']);
        }
        
        $table = cls::get('core_TableView', array('mvc' => $this));
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        
        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
        
        return $tpl;
    }
    
    
    /**
     * Връща опаковката ако има такава
     * 
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката
     * @return stdClass
     */
    public static function getPack($productId, $packagingId)
    {
        return self::fetch("#productId = {$productId} AND #packagingId = '{$packagingId}'");
    }
    

    /**
     * Връща количеството на дадения продукт в посочената опаковка
     */
    public static function getQuantityInPack($productId, $pack = 'pallet')
    { 
        $uomRec = cat_UoM::fetchBySinonim(mb_strtolower($pack));
        if($uomRec) {

            $packRec = self::getPack($productId, $uomRec->id);
            if($packRec) return $packRec->quantity;
        }
    }
    

    /**
     * Връща най-голямата опаковка, която има по-малко бройки в себе си, от посоченото
     */
    public static function getLowerPack($productId, $quantity)
    {
        $bestRec = NULL;

        $query = self::getQuery();
        while($rec = $query->fetch("#productId = {$productId}")) {
            if($rec->quantity < $quantity) {
                if(!$bestRec || $bestRec->quantity < $rec->quantity) {
                    $bestRec = $rec;
                }
            }
        }

        return $bestRec;
    }

    
    /**
     * Дали в бизнес документите е използван артикула с посочената опаковка
     * 
     * @param int $productId   - ид на артикул
     * @param int $uomId       - мярка на артикула
     * @param boolean $cache   - дали искаме данните да се кешират при използване или не
     * @return boolean $isUsed -използван или не
     */
    public static function isUsed($productId, $uomId, $cache = FALSE)
    {  
        $cacheKey = "{$productId}|{$uomId}";

        // Ако искаме кеширани данни
        if($cache === TRUE){
            $isUsed = FALSE;
            
            // Проверяваме имали кеш
            $hasCache = core_Cache::get('cat_Products',  $cacheKey);
            
            // Ако артикула е използван в тази си опаковка, кешираме че е използван
            if($hasCache !== 'y' && $hasCache !== 'n'){
                
                // Ако няма проверяваме дали е използван с тази опаковка (без кеш)
                if(self::isUsed($productId, $uomId)){  
                    core_Cache::set('cat_Products', $cacheKey, 'y', 10080);
                    $isUsed = TRUE;
                } else {
                    core_Cache::set('cat_Products', $cacheKey, 'n', 10080);
                    $isUsed = FALSE;
                }
            } else {
              
                $isUsed = ($hasCache == 'y');
            }
            
            // Връщаме намерения резултат
            return $isUsed;
        }
        
        // Детайли в които ще проверяваме
        $details = array('sales_SalesDetails', 
                         'purchase_PurchasesDetails', 
                         'store_ShipmentOrderDetails', 
                         'store_ReceiptDetails', 
                         'sales_QuotationsDetails', 
                         'sales_InvoiceDetails', 
                         'purchase_InvoiceDetails', 
                         'planning_DirectProductNoteDetails',
                         'planning_ConsumptionNoteDetails', 
                         'cat_BomDetails', 
                         'sales_ProformaDetails', 
                         'sales_ServicesDetails', 
                         'purchase_ServicesDetails', 
                         'store_ConsignmentProtocolDetailsReceived', 
                         'store_ConsignmentProtocolDetailsSend');
        
        // За всеки от изброените документи проверяваме дали е избран артикула с мярката
        $isUsed = FALSE;
        foreach ($details as $Detail){
            if($Detail == 'cat_BomDetails'){
                if($rec = $Detail::fetch("#resourceId = {$productId} AND #packagingId = '{$uomId}'", 'id')){
                    $isUsed = TRUE;
                    break;
                }
            } else {
                if($rec = $Detail::fetch("#productId = {$productId} AND #packagingId = '{$uomId}'", 'id')){
                    $isUsed = TRUE;
                    break;
                }
            }
        }
        
        // Ако няма проверяваме дали е използван с тази опаковка (без кеш)
        if($isUsed){
            core_Cache::set('cat_Products', $cacheKey, 'y', 10080);
        } else {
            core_Cache::set('cat_Products', $cacheKey, 'n', 10080);
        }

        // Връщаме резултат
        return $isUsed;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	// Създаване на нов шаблон на опаковката при нужда
    	$uomType = cat_UoM::fetchField($rec->packagingId, 'type');
    	if($uomType == 'packaging'){
    		cat_PackParams::sync($rec->packagingId, $rec->sizeWidth, $rec->sizeHeight, $rec->sizeDepth, $rec->tareWeight);
    	}
    }
}
