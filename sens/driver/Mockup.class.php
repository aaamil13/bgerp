<?php



/**
 * Имитация на драйвер за IP сензор
 *
 *
 * @category  all
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвери на сензори
 */
class sens_driver_Mockup extends sens_driver_IpDevice
{
    
    
    /**
     * Брой последни стойности на базата на които се изчислява средна стойност
     * @var integer
     */
    var $avgCnt = 10;
    
    
    /**
     * Параметри които чете или записва драйвера
     */
    var $params = array(
        'T' => array('unit'=>'T', 'param'=>'Температура', 'details'=>'C'),
        'Hr' => array('unit'=>'Hr', 'param'=>'Влажност', 'details'=>'%'),
        'Dst' => array('unit'=>'Dst', 'param'=>'Запрашеност', 'details'=>'%'),
        'Chm' => array('unit'=>'Chm', 'param'=>'Хим. замърсяване', 'details'=>'%'),
        'avgHr' => array('unit'=>'avgHr', 'param'=>'Средна влажност', 'details'=>'%'),
        // Ако искаме описваме и изходите за да можем да ги следим в логовете
        'OutD1' => array('unit'=>'Out1', 'param'=>'Изход 1', 'details'=>'(ON/OFF)'),
        'OutD2' => array('unit'=>'Out2', 'param'=>'Изход 2', 'details'=>'(ON/OFF)'),
        'OutA1' => array('unit'=>'Out3', 'param'=>'Изход 3', 'details'=>'(1..10)')
    );
    
    
    /**
     * Описания на изходите ако има такива
     * Съдържащите 'D' - digital, 'A' - analog
     */
    var $outs = array(
        'OutD1' => array('digital' => array('0', '1')),
        'OutD2' => array('digital' => array('0', '1')),
        'OutA1' => array('analog' => array('0', '10'))
    );
    
    
    /**
     * Брой аларми
     */
    var $alarmCnt = 3;
    
    
    /**
     * Извлича данните от формата със заредени от Request данни,
     * като може да им направи специализирана проверка коректност.
     * Ако след извикването на този метод $form->getErrors() връща TRUE,
     * то означава че данните не са коректни.
     * От формата данните попадат в тази част от вътрешното състояние на обекта,
     * която определя неговите settings
     *
     * @param object $form
     */
    function setSettingsFromForm($form)
    {
    
    }
    
    
    /**
     * Подготвя формата за настройки на сензора
     * и алармите в зависимост от параметрите му
     */
    function prepareSettingsForm($form)
    {
        $this->getSettingsForm($form);
    }
    
    
    /**
     * Прочита текущото състояние на драйвера/устройството
     */
    function updateState()
    {
        
        $stateOld = $this->loadState();
        
        $state = array();
        
        foreach ($this->params as $param => $dummy) {
            switch ($param) {
                case 'T' :
                    $state[$param] = $stateOld[$param] + rand(-2, 2);
                    
                    if (date("H") > "08" && date("H") < "19") $state[$param] += 0.1;
                    
                    if (date("H") < "08" || date("H") > "19") $state[$param] -= 0.1;
                    break;
                case 'Hr' :
                    $state[$param] = rand(0, 100);
                    break;
                case 'avgHr' :
                    // Тук взимаме историята на влажностите за изчисляването на средната стойност
                    $state['avgHrArr'] = $stateOld['avgHrArr'];
                    
                    $ndx = ((int)time() / 60) % $this->avgCnt;
                    $state['avgHrArr'][$ndx] = $state['Hr'];
                    $state[$param] = array_sum($state['avgHrArr']) / count($state['avgHrArr']);
                    break;
                default :
                if (!isset($this->outs[$param])) {
                    $state[$param] = '';    // Не е зададен начин на изчисление /все едно не е закачен датчик/
                }
                break;
            }
        }
        
        $outs = permanent_Data::read('sens_driver_mockupOuts');
        
        $this->stateArr = array_merge((array)$outs, $state);
        
        // Връщаме TRUE при успешно четене
        return TRUE;
    }
    
    
    /**
     * Сетва изходите на драйвера по зададен масив
     * @param array() $newOuts
     * @return bool
     */
    function setOuts($newOuts)
    {
        // Сетваме изходите според масива $outs
        foreach ($this->outs as $out => $type) {
            $outs[$out] = $newOuts[$out];
        }
        
        // За Ментак-а ползваме permanent_Data за да предаваме състоянието
        permanent_Data::write('sens_driver_mockupOuts', $outs);
    }
}