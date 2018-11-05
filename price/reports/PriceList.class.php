<?php


/**
 * Драйвер за Ценоразписи
 *
 *
 * @category  bgerp
 * @package   price
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Ценоразписи
 */
class price_reports_PriceList extends frame2_driver_TableData
{
    

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'sales, priceDealer, ceo';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField = 'productId';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'groupName';
    
    
    /**
     * Закръгляне на цените по подразбиране
     */
    const DEFAULT_ROUND = 5;
    
    
    /**
     * Какъв да е класа на групирания ред
     */
    protected $groupByFieldClass = 'pricelist-group-label';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('date', 'date(smartTime)', 'caption=Към дата,after=title');
        $fieldset->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandatory,after=date');
        $fieldset->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)', 'caption=Валута,input,after=policyId,single=none');
        $fieldset->FLD('vat', 'enum(yes=с включено ДДС,no=без ДДС)', 'caption=ДДС,after=currencyId,single=none');
        $fieldset->FLD('productGroups', 'keylist(mvc=cat_Groups,select=name,makeLinks,allowEmpty)', 'caption=Групи,columns=2,placeholder=Всички,after=vat,single=none');
        $fieldset->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки,columns=3,placeholder=Всички,after=productGroups,single=none');
        $fieldset->FLD('period', 'time(suggestions=1 ден|1 седмица|1 месец)', 'caption=Изменени цени,after=packagings,single=none');
        $fieldset->FLD('lang', 'enum(auto=Текущ,bg=Български,en=Английски)', 'caption=Допълнително->Език,after=period');
        $fieldset->FLD('displayDetailed', 'enum(no=Съкратен изглед,yes=Разширен изглед)', 'caption=Допълнително->Артикули,after=lang,single=none');
        $fieldset->FLD('showMeasureId', 'enum(yes=Показване,no=Скриване)', 'caption=Допълнително->Основна мярка,after=displayDetailed');
        $fieldset->FLD('showEan', 'enum(yes=Показване ако има,no=Да не се показва)', 'caption=Допълнително->EAN|*?,after=showMeasureId');
        $fieldset->FLD('round', 'int(Min=0)', 'caption=Допълнително->Точност,autohide,after=showEan');
   }
    
   
   /**
    * Връща заглавието на отчета
    *
    * @param stdClass $rec - запис
    *
    * @return string|NULL - заглавието или NULL, ако няма
    */
   public function getTitle($rec)
   {
       $policyName = price_Lists::getTitleById($rec->policyId);
       $title = "Ценоразпис \"{$policyName}\"";
       
       return $title;
   }
   
   
   /**
    * Преди показване на форма за добавяне/промяна.
    *
    * @param frame2_driver_Proto $Driver   $Driver
    * @param embed_Manager       $Embedder
    * @param stdClass            $data
    */
   protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
   {
       $form = &$data->form;
       $form->setField('round', "placeholder=" . self::DEFAULT_ROUND);
       $form->setDefault('lang', 'auto');
       $form->setDefault('showEan', 'yes');
       $form->setDefault('showMeasureId', 'yes');
       $form->setDefault('displayDetailed', 'no');
       
       $suggestions = cat_UoM::getPackagingOptions();
       $form->setSuggestions('packagings', $suggestions);
       $form->setOptions('policyId', price_ListDocs::getDefaultPolicies($form->rec));
       
       // Ако е в папка на контрагент
       $Cover = doc_Folders::getCover($form->rec->folderId);
       if($Cover->haveInterface('crm_ContragentAccRegIntf')){
           $defaultList = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
           $form->setDefault('policyId', $defaultList);
           $form->setDefault('vat', deals_Helper::getDefaultChargeVat($form->rec->folderId));
           $form->setDefault('currencyId', $Cover->getDefaultCurrencyId());
       }
       
       // Ако е в папка с контрагентски данни
       if($Cover->haveInterface('doc_ContragentDataIntf')){
           $cData = doc_Folders::getContragentData($form->rec->folderId);
           $bgId = drdata_Countries::fetchField("#commonName = 'Bulgaria'", 'id');
           $lang = (!empty($cData->countryId) && $cData->countryId != $bgId) ? 'en' : 'bg';
           $form->setDefault('lang', $lang);
       }
   }
    
   
   /**
    * Кои записи ще се показват в таблицата
    *
    * @param stdClass $rec
    * @param stdClass $data
    *
    * @return array
    */
   protected function prepareRecs($rec, &$data = null)
   {
       $date = ($rec->date == dt::today()) ? dt::now() : "{$rec->date} 23:59:59";
       $dateBefore = (!empty($rec->period)) ? (dt::addSecs(-1 * $rec->period, $date, false) . " 23:59:59") : null;
       $round = !empty($rec->round) ? $rec->round : self::DEFAULT_ROUND;
       
       // Извличане на стандартните, продаваеми артикули от посочените групи
       $params = array('onlyPublic' => true);
       if(!empty($rec->productGroups)){
           $params['groups'] = $rec->productGroups;
       }
       $sellableProducts = array_keys(price_ListRules::getSellableProducts($params));
       
       $recs = array();
       if(is_array($recs)) {
           
           // Ако няма опаковки, това са всички
           $currencyRate = currency_CurrencyRates::getRate($rec->date, $rec->currencyId, acc_Periods::getBaseCurrencyCode($rec->date));
           $packArr = (!empty($rec->packagings)) ? keylist::toArray($rec->packagings) : arr::make(array_keys(cat_UoM::getPackagingOptions(), true));
           
           // За всеки продаваем стандартен артикул
           foreach ($sellableProducts as $id) {
               $productRec = cat_Products::fetch($id, 'groups,code,measureId,name,isPublic');
               
               $obj = (object) array('productId' => $productRec->id,
                                           'code' => (!empty($productRec->code)) ? $productRec->code : "Art{$productRec->id}",
                                           'measureId' => $productRec->measureId,
                                           'vat' => cat_Products::getVat($productRec->id, $date),
                                           'packs' => array(),
                                           'groups' => $productRec->groups);
               
               // Изчислява се цената по избраната политика
               $priceByPolicy = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $date);
               $obj->name = cat_Products::getVerbal($productRec, 'name');
               $obj->price = deals_Helper::getDisplayPrice($priceByPolicy, $obj->vat, $currencyRate, $rec->vat);
               
               // Ако има избран период в който да се гледа променена ли е цената
               if(isset($dateBefore)){
                   $oldPrice = price_ListRules::getPrice($rec->policyId, $productRec->id, null, $dateBefore);
                   $oldPrice = round($oldPrice, 2);
                   
                   // Колко процента е промяната спрямо старата цена
                   if(empty($oldPrice)){
                       $obj->type = 'new';
                       $difference = 1;
                   } elseif(!empty($oldPrice) && empty($priceByPolicy)){
                       $obj->type = 'removed';
                       $difference = -1;
                   } else {
                       $difference = (round(trim($priceByPolicy), $round) - trim($oldPrice)) / $oldPrice;
                       $difference = round($difference, 2);
                   }
                   
                   // Ако няма промяна, артикулът не се показва
                   if($difference == 0) continue;
                   $obj->difference = $difference;
               }
               
               // Ако има цена, показват се и избраните опаковки с техните цени
               if(!empty($priceByPolicy)) {
                   $packQuery = cat_products_Packagings::getQuery();
                   $packQuery->where("#productId = {$productRec->id}");
                   $packQuery->in('packagingId', $packArr);
                   $packQuery->show("eanCode,quantity,packagingId");
                   while($packRec = $packQuery->fetch()){
                       $packRec->price = $packRec->quantity * $priceByPolicy;
                       $packRec->price = deals_Helper::getDisplayPrice($packRec->price, $obj->vat, $currencyRate, $rec->vat);
                       $obj->packs[$packRec->packagingId] = $packRec;
                   }
                   
                   // Ако ще се скрива мярката и няма опаковки, няма какво да се показва, освен ако артикула не е бил премахнат
                   if($rec->showMeasureId != 'yes' && !count($obj->packs)) continue;
               }
               
               if($obj->type != 'removed' && empty($priceByPolicy)) continue;
               
               $recs[$id] = $obj;
           }
       }
      
       // Ако има подговени записи
       if(count($recs)){
           
           // Ако няма избрани групи, търсят се всички
           $productGroups = $rec->productGroups;
           if(empty($productGroups)){
               $productGroups = arr::extractValuesFromArray(cat_Groups::getQuery()->fetchAll(), 'id');
               $productGroups = keylist::fromArray($productGroups);
           }
           
           // Филтриране на артикулите според избраните групи
           if($rec->lang != 'auto'){
               core_Lg::push($rec->lang);
           }
           store_InventoryNoteSummary::filterRecs($productGroups, $recs, 'code', 'name');
           if($rec->lang != 'auto'){
               core_Lg::pop();
           }
       }
       
       return $recs;
   }
    
   
   /**
    * Вербализиране на редовете, които ще се показват на текущата страница в отчета
    *
    * @param stdClass $rec  - записа
    * @param stdClass $dRec - чистия запис
    *
    * @return stdClass $row - вербалния запис
    */
   protected function detailRecToVerbal($rec, &$dRec)
   {
       $row = new stdClass();
       
       $display = ($rec->displayDetailed == 'yes') ? 'detailed' : 'short';
       $row->productId = cat_Products::getAutoProductDesc($dRec->productId, null, $display, 'public', $rec->lang, null, false);
       
       $row->groupName = core_Type::getByName('varchar')->toVerbal($dRec->groupName);
       $row->code = core_Type::getByName('varchar')->toVerbal($dRec->code);
       $row->measureId = tr(cat_UoM::getShortName($dRec->measureId));
       
       $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
       $row->price = core_Type::getByName("double(decimals={$decimals})")->toVerbal($dRec->price);
       
       // Рендиране на опаковките в таблица
       if(count($dRec->packs)){
           $row->packs = $this->getPackTable($rec, $dRec);
       }
       
       // Показване на процента промяна
       if(!empty($rec->period)){
           if($dRec->type == 'new'){
               $row->difference = "<span class='price-list-new-item'>" . tr('Нов') . "</span>";
           } elseif($dRec->type == 'removed'){
               $row->difference = "<span class='price-list-removed-item'>" . tr('Премахнат') . "</span>";
           }else {
               $row->difference = core_Type::getByName('percent')->toVerbal($dRec->difference);
               if($dRec->difference > 0){
                   $row->difference = "<span class='green'>+{$row->difference}</span>";
               } else {
                   $row->difference = "<span class='red'>{$row->difference}</span>";
               }
           }
       }
       
       return $row;
   }
    
   
   /**
    * Рендиране на таблицата с опаковките
    * 
    * @param stdClass $rec
    * @param stdClass $dRec
    * @return core_ET $tpl
    */
   private function getPackTable($rec, $dRec)
   {
       $rows = array();
       
       // Вербализиране на опаковките ако има
       foreach ($dRec->packs as $packRec){
           $packName = cat_UoM::getVerbal($packRec->packagingId, 'name');
           deals_Helper::getPackInfo($packName, $dRec->productId, $packRec->packagingId, $packRec->quantity);
           $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
           $rows[$packRec->packagingId] = (object)array('packagingId' => $packName, 'price' => core_Type::getByName("double(decimals={$decimals})")->toVerbal($packRec->price));
           if(!empty($packRec->eanCode)){
               $eanCode = core_Type::getByName('varchar')->toVerbal($packRec->eanCode);
               if(!Mode::isReadOnly() && barcode_Search::haveRightFor('list')){
                   $eanCode = ht::createLink($eanCode, array('barcode_Search', 'search' => $eanCode));
               }
               $rows[$packRec->packagingId]->eanCode = $eanCode;
           }
       }
       
       $fieldset = new core_FieldSet();
       $fieldset->FLD('eanCode', 'varchar', 'tdClass=small');
       $fieldset->FLD('price', 'varchar', 'smartCenter');
       
       // Рендиране на таблицата, в която ще се показват опаковките
       $table = cls::get('core_TableView', array('mvc' => $fieldset));
       $table->tableClass = 'pricelist-report-pack-table';
       $table->thHide = true;
       $listFields = arr::make('eanCode=ЕАН,packagingId=Опаковка,price=Цена', true);
       if($rec->showEan != 'yes'){
           unset($listFields['eanCode']);
       }
       
       $tpl = $table->get($rows, $listFields);
       
       return $tpl;
   }
   
   
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec    - записа
     * @param bool     $export - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        $decimals = isset($rec->round) ? $rec->round : self::DEFAULT_ROUND;
        if($export === true){
            $fld->FLD('groupName', 'varchar', 'caption=Група');
        }
        $fld->FLD('code', 'varchar', 'caption=Код,tdClass=centered');
        $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
        if($export === true){
            $fld->FLD('eanCode', 'varchar', 'caption=ЕАН');
        }
        if($rec->showMeasureId == 'yes' || $export === true){
            $fld->FLD('measureId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered nowrap');
            $fld->FLD('price', "double(decimals={$decimals})", 'caption=Цена');
        }
        if($export === true){
            $fld->FLD('currencyId', 'varchar', 'caption=Валута');
        } else {
            $fld->FLD('packs', 'html', 'caption=Опаковки');
        }
        if(!empty($rec->period)){
            $fld->FLD('difference', 'percent', "caption=Промяна");
        }
        
        return $fld;
    }
    
    
    /**
     * Какъв ще е езика с който ще се рендират данните на шаблона
     *
     * @param stdClass $rec
     *
     * @return string|null езика с който да се рендират данните
     */
    public function getRenderLang($rec)
    {
        return ($rec->lang == 'auto') ? null : $rec->lang;
    }
    
    
    /**
     * рендиране на таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return core_ET $tpl
     */
    protected function renderTable($rec, &$data)
    {
        $tpl = parent::renderTable($rec, $data);
        $vatRow = core_Type::getByName('enum(yes=с включено ДДС,no=без ДДС)')->toVerbal($rec->vat);
        $beforeRow = tr("Всички цени са в|* {$rec->currencyId}, |{$vatRow}|*");
        $tpl->prepend($beforeRow, 'TABLE_BEFORE');
        
        return $tpl;
    }
    
    
    /**
     * След вербализирането на данните
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $row
     * @param stdClass            $rec
     * @param array               $fields
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $row->policyId = price_Lists::getHyperlink($rec->policyId, true);
        $row->productGroups = (!empty($rec->productGroups)) ? implode(', ', cat_Groups::getLinks($rec->productGroups)) : tr('Всички');
        $row->packagings = (!empty($rec->packagings)) ? core_Type::getByName('keylist(mvc=cat_UoM,select=name)')->toVerbal($rec->packagings): tr('Всички');
         
        if(!empty($rec->period)){
            $row->period = core_Type::getByName('time')->toVerbal($rec->period);
            $row->periodDate = dt::mysql2verbal(dt::addSecs(-1 * $rec->period, $rec->date, false), 'd.m.Y');
        }
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        if(Mode::is('printing')) return;
        
        $fieldTpl = new core_ET(tr("|*<fieldset class='detail-info'>
                                <legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div>|Цени към дата|*: <b>[#date#]</b></div>
                                <!--ET_BEGIN period--><div>|Изменени за|*: [#period#] (|от|* [#periodDate#])</div><!--ET_END period-->
                                <div>|Групи|*: [#productGroups#]</div><div>|Опаковки|*: [#packagings#]</div></small>"));
    
        foreach (array('periodDate', 'date', 'period', 'productGroups', 'packagings') as $field){
            $fieldTpl->replace($data->row->{$field}, $field);
        }
            
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * При събмитване на формата
     *
     * @param frame2_driver_Proto $Driver   $Driver
     * @param embed_Manager       $Embedder
     * @param core_Form           $form
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if(empty($form->rec->date)){
                $form->rec->date = dt::now();
            }
            
            if (cat_Groups::checkForNestedGroups($form->rec->productGroups)) {
                $form->setError('productGroups', 'Избрани са вложени групи');
            }
        }
    }
    
    
    /**
     * Връща редовете, които ще се експортират от справката
     *
     * @param stdClass       $rec         - запис
     * @param core_BaseClass $ExportClass - клас за експорт (@see export_ExportTypeIntf)
     *
     * @return array                      - записите за експорт
     */
    protected function getRecsForExport($rec, $ExportClass)
    {
        $exportRecs = array();
        foreach ($rec->data->recs as $dRec){
            $clone = clone $dRec;
            $clone->currencyId = $rec->currencyId;
            
            $exportRecs[] = $clone;
            if(count($dRec->packs)){
                foreach ($dRec->packs as $packRec){
                    $clone1 = clone $clone;
                    $clone1->packs = array();
                    $clone1->price = $packRec->price;
                    $clone1->eanCode = $packRec->eanCode;
                    $clone1->measureId = $packRec->packagingId;
                    
                    $exportRecs[] = $clone1;
                }
            }
        }
        
        return $exportRecs;
    }
}