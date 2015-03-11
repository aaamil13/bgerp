<?php



/**
 * Плъгин за импорт на артикули към бизнес документи. Закача се към техен детайл който има интерфейс 'deals_DealImportCsvIntf'
 * За да се импортират csv данни се минава през няколко стъпки с помощна
 * на експерта (@see expert_Expert).
 *
 * Целта е да се уточни:
 * 1. Как се въвеждат csv данните с ъплоуд на файл или с copy & paste
 * 2. Какви са разделителят, ограждането и първия ред на данните
 * 3. Кои колони от csv-to на кои полета от мениджъра отговарят.
 *
 * След определянето на тези данни драйвъра се грижи за правилното импортиране
 *
 * Мениджъра в който ще се импортира и кои полета от него ще бъдат попълнени
 * се определя от драйвъра.
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_ImportDealDetailProduct extends core_Plugin
{
	
	
	/**
	 * Извиква се след описанието на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		$mvc->declareInterface('deals_DealImportProductIntf');
	}
	
	
	/**
	 * Преди всеки екшън на мениджъра-домакин
	 */
	public static function on_BeforeAction($mvc, &$tpl, $action)
	{
		if($action == 'import'){
			$mvc->requireRightFor('import');
	
			$form = cls::get('core_Form');
			
			// Подготвяме формата
			$form->FLD($mvc->masterKey, "key(mvc={$mvc->Master->className})", 'input=hidden,silent');
			$form->input(NULL, 'silent');
			$form->title = 'Импортиране на артикули към|*' . " <b>" . $mvc->Master->getRecTitle($form->rec->{$mvc->masterKey}) . "</b>";
			self::prepareForm($form);
			
			$form->input();
			
			// Ако формата е импутната
			if($form->isSubmitted()){
				$rec = &$form->rec;
				
				// Трябва да има посочен източник
				if((empty($rec->csvData) && empty($rec->csvFile)) || (!empty($rec->csvData) && !empty($rec->csvFile))){
					$form->setError('csvData,csvFile', 'Трябва да е попълнено само едно поле');
				}
				
				if(!$form->gotErrors()){
					$data = ($rec->csvFile) ? bgerp_plg_Import::getFileContent($rec->csvFile) : $rec->csvData;
					if($rec->delimiter == '\t'){
						$rec->delimiter = "\t";
					}
					
					// Обработваме данните
					$rows = csv_Lib::getCsvRows($data, $rec->delimiter, $rec->enclosure, $rec->firstRow);
					$fields = array('code' => $rec->codecol, 'quantity' => $rec->quantitycol, 'price' => $rec->pricecol);
					
					// Ако можем да импортираме импортираме
					if($mvc->haveRightFor('import')){
						
						// Импортиране на данните от масива в зададените полета
						$msg = self::importRows($mvc, $rec->{$mvc->masterKey}, $rows, $fields);
						
						// Редирект кум мастъра на документа към който ще импортираме
						return Redirect(array($mvc->Master, 'single', $rec->{$mvc->masterKey}), 'FALSE', $msg);
					}
				}
			}
			
			// Рендиране на опаковката
			$tpl = $mvc->renderWrapping($form->renderHtml());
	
			return FALSE;
		}
	}
	
	
	/**
	 * Подготовка на формата за импорт на артикули
	 * @param unknown $form
	 */
	private static function prepareForm(&$form)
	{
		// Полета за орпеделяне на данните
		$form->FLD("csvData", 'text(1000000)', 'width=100%,caption=Данни');
		$form->FLD("csvFile", 'fileman_FileType(bucket=bnav_importCsv)', 'width=100%,caption=CSV файл');
		
		// Настройки на данните
		$form->FLD("delimiter", 'varchar(1,size=5)', 'width=100%,caption=Настройки->Разделител');
		$form->FLD("enclosure", 'varchar(1,size=3)', 'width=100%,caption=Настройки->Ограждане');
		$form->FLD("firstRow", 'enum(columnNames=Имена на колони,data=Данни)', 'width=100%,caption=Настройки->Първи ред');
		$form->setSuggestions("delimiter", array(',' => ',', ';' => ';', ':' => ':', '|' => '|', '\t' => 'Таб'));
		$form->setSuggestions("enclosure", array('"' => '"', '\'' => '\''));
		$form->setDefault("delimiter", ',');
		$form->setDefault("enclosure", '"');
		
		// Съответстващи колонки на полета
		$form->FLD('codecol', 'int', 'caption=Съответствие в данните->Код,unit=колона,mandatory');
		$form->FLD('quantitycol', 'int', 'caption=Съответствие в данните->К-во,unit=колона,mandatory');
		$form->FLD('pricecol', 'int', 'caption=Съответствие в данните->Цена,unit=колона');
		
		foreach (array('codecol', 'quantitycol', 'pricecol') as $i => $fld){
			$form->setSuggestions($fld, array(1,2,3,4,5,6,7));
			$form->setDefault($fld, $i + 1);
		}
		
		$form->toolbar->addSbBtn('Import', 'save', 'ef_icon = img/16/импоер16.png, title = Импорт');
		$form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close16.png, title=Прекратяване на действията');
	}
	
	
	/**
	 * Импортиране на записите ред по ред от мениджъра
	 */
	private static function importRows($mvc, $masterId, $rows, $fields)
	{
		$added = $failed = 0;
		
		foreach ($rows as $row){
			
			// Подготвяме данните за реда
			$obj = (object)array('code'     => $row[$fields['code']],
						         'quantity' => $row[$fields['quantity']],
					             'price'    => $row[$fields['price']]
			);
			
			// Подсигуряваме се че подадените данни са във вътрешен вид
			$obj->code = cls::get('type_Varchar')->fromVerbal($obj->code);
			$obj->quantity = cls::get('type_Double')->fromVerbal($obj->quantity);
			if($obj->price){
				$obj->code = cls::get('type_Varchar')->fromVerbal($obj->code);
			}
			
			// Ако не е намерен код или к-во не правим нищо
			if(is_null($obj->code) || is_null($obj->quantity)) {
				$failed++;
				continue;
			}
			
			// Опитваме се да импортираме записа
			try{
				if($mvc->import($masterId, $obj)){
					$added++;
				}
			} catch(core_exception_Expect $e){
				$failed++;
			}
		}
		
		$msg = "Импортирани са |{$added}|* артикула";
		if($failed != 0){
			$msg .= ". Не са импортирани |{$failed}|* артикула";
		}
		
		return tr($msg);
	}
	
	
	/**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, $data)
	{
		$masterRec = $data->masterData->rec;
		
		if($mvc->haveRightFor('add', (object)array("{$mvc->masterKey}" => $masterRec->id))){
			if($mvc->haveRightFor('import', (object)array("{$mvc->masterKey}" => $masterRec->id))){
				$data->toolbar->addBtn('Импорт', array($mvc, 'import', "{$mvc->masterKey}" => $masterRec->id, 'ret_url' => TRUE),
				"id=btnAdd-import,{$error},title=Импортиране на артикули", 'ef_icon = img/16/import16.png,order=15');
			}
		}
	}
}