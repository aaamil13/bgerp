<?php


/**
 * Клас  'rack_PositionType' - Тип за позиция в складовото пространство
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class rack_PositionType extends type_Varchar
{
    /**
     * Параметър определящ максималната широчина на полето
     */
    public $maxFieldSize = 9;
    
    
    /**
     * Колко символа е дълго полето в базата
     */
    public $dbFieldLen = 9;
    
    
    /**
     * Константа за пода
     */
    const FLOOR = 'floor';
    
    
    /**
     * Този метод трябва да конвертира от вербално към вътрешно представяне дадената стойност
     */
    public function fromVerbal($value)
    {
        if (!trim($value)) {
            
            return;
        }
        
        core_Lg::push('en');
        $checkValue = strtolower(tr($value));
        core_Lg::pop('en');
        
        if($checkValue == self::FLOOR){
            return self::FLOOR;
        }
        
        $matches = array();
        preg_match('/([0-9]{1,3})[\\-]{0,1}([a-z])[\\-]{0,1}([0-9]{1,3})/i', $value, $matches);
        
        if (!is_array($matches) || count($matches) != 4) {
            $this->error = 'Невалиден синтаксис';
            
            return false;
        }
        
        return strtoupper(((int) $matches[1]) . '-' . $matches[2] . '-' . ((int) $matches[3]));
    }
    
    
    /**
     * Преобразува позицията във вербален вид
     */
    public function toVerbal($value)
    {
        if($value == self::FLOOR){
            $value = tr('Под');
        }
        
        if (!strpos($value, '-') || Mode::is('printing') || Mode::is('text', 'plain') || Mode::is('text', 'printing')) {
            
            return $value;
        }
        
        list($n, $r, $c) = explode('-', $value);
        
        $res = ht::createLink($value, array('rack_Racks', 'show', $n, 'pos' => "{$n}-{$r}-{$c}"));
        
        return $res;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    public function defVal()
    {
        return '';
    }
}
