<?php


/**
 * Базов драйвер за драйвер на артикул
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Дата
 */
class cond_type_Date extends cond_type_Proto
{
	
	
	/**
	 * Кой базов тип наследява
	 */
	protected $baseType = 'type_Date';
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('time', 'enum(no=Без час, yes=С час)', 'caption=Конкретизиране->Дължина,before=default');
	}
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param stdClass $rec - запис
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$Type = parent::getType($rec);
	
		if($rec->time == 'yes'){
			$Type = cls::get('type_DateTime');
		}
	
		return $Type;
	}
}