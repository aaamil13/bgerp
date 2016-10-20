<?php


/**
 * Тип за параметър 'Множество'
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Множество
 */
class cond_type_Set extends cond_type_Proto
{
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		$fieldset->FLD('options', 'text', 'caption=Конкретизиране->Опции,before=default,mandatory');
	}
	
	
	/**
	 * Връща инстанция на типа
	 *
	 * @param stdClass $rec - запис
	 * @return core_Type - готовия тип
	 */
	public function getType($rec)
	{
		$options = static::text2options($rec->options);
		$options = arr::fromArray($options);
		
		$Type = core_Type::getByName("set($options)");
		
		return $Type;
	}
}