<?php


/**
 * Клас 'cond_TransportCalc' - Интерфейс за класове, които определят цената за транспорт
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Milen Georgiev <milen@experta.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_TransportCalc
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'tcost_CostCalcIntf';
    
    
    /**
     * Инстанция на мениджъра имащ интерфейса
     */
    public $class;
    
    
    /**
     * Стойността, която ще се върне ако е не може да се намери зона
     */
    const ZONE_FIND_ERROR = -2;
    
    
    /**
     * Стойността, която ще се върне ако има грешка при деление
     */
    const DELIMITER_ERROR = -4;
    
    
    /**
     * Стойността, която ще се върне ако артикула няма тегло
     */
    const EMPTY_WEIGHT_ERROR = -8;
    
    
    /**
     * Определяне на обемното тегло, на база на обема на товара
     *
     * @param float $weight - Тегло на товара
     * @param float $volume - Обем  на товара
     *
     * @return float - Обемно тегло на товара
     */
    public function getVolumicWeight($weight, $volume)
    {
        return $this->class->getVolumicWeight($weight, $volume);
    }
    
    
    /**
     * Определяне цената за транспорт при посочените параметри
     *
     * @param int   $deliveryTermId - условие на доставка
     * @param float $singleWeight   - тегло
     * @param float $singleVolume   - обем
     * @param int   $totalWeight    - Общо тегло на товара
     * @param int   $totalVolume    - Общ обем на товара
     * @param array $params         - Други параметри
     *
     * @return array
     *               ['fee']              - цена, която ще бъде платена за теглото на артикул, ако не може да се изчисли се връща < 0
     *               ['deliveryTime']     - срока на доставка в секунди ако го има
     */
    public function getTransportFee($deliveryTermId, $singleWeight, $singleVolume, $totalWeight, $totalVolume, $params)
    {
        return $this->class->getTransportFee($deliveryTermId, $singleWeight, $singleVolume, $totalWeight, $totalVolume, $params);
    }
    
    
    /**
     * Добавя полета за доставка към форма
     *
     * @param core_FieldSet $form
     * @param string|NULL   $userId
     *
     * @return void
     */
    public function addFields(core_FieldSet &$form, $userId = null)
    {
        return $this->class->addFields($form, $userId);
    }
    
    
    /**
     * Добавя масив с полетата за доставка
     *
     * @return array
     */
    public function getFields()
    {
        return $this->class->getFields();
    }
    
    
    /**
     * Проверява форма
     *
     * @param core_FieldSet $form
     *
     * @return void
     */
    public function checkForm(core_FieldSet &$form)
    {
        return $this->class->checkForm($form);
    }
    
    
    /**
     * Рендира информацията
     *
     * @param stdClass rec
     *
     * @return core_ET $tpl
     */
    public function renderDeliveryInfo($rec)
    {
        return $this->class->renderDeliveryInfo($rec);
    }
}
