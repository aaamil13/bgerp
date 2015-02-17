<?php


/**
 * Клас  'type_UserOrRole' - Възможност за избиране на потребители или роля
 *
 *
 * @category  ef
 * @package   type
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_UserOrRole extends type_User
{
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->params['mvc'], 'core_Users');
        
        setIfNot($this->params['userSelect'], 'names');
        $this->params['select'] = $this->params['userSelect'];
        
        setIfNot($this->params['userRoles'], 'user');
        $this->params['userRoles'] = str_replace("|", ",", $this->params['userRoles']);
        $this->params['roles'] = $this->params['userRoles'];
        
        setIfNot($this->params['userRolesForTeams'], 'ceo, admin');
        $this->params['userRolesForTeams'] = str_replace("|", ",", $this->params['userRolesForTeams']);
        $this->params['rolesForTeams'] = $this->params['userRolesForTeams'];
        
        setIfNot($this->params['userRolesForAll'], 'ceo, admin');
        $this->params['userRolesForAll'] = str_replace("|", ",", $this->params['userRolesForAll']);
        $this->params['rolesForAll'] = $this->params['userRolesForAll'];
        
        setIfNot($this->params['rolesForAllSysTeam'], 'ceo, admin');
        $this->params['rolesForAllSysTeam'] = str_replace("|", ",", $this->params['rolesForAllSysTeam']);
        
        setIfNot($this->params['rolesForAllRoles'], 'ceo, admin');
        $this->params['rolesForAllRoles'] = str_replace("|", ",", $this->params['rolesForAllRoles']);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     */
    public function prepareOptions()
    {
        // Ако има съответната роля за виждане на ролите
        if (haveRole($this->params['rolesForAllRoles'])) {
            
            // Добавяме неизбираемо поле за оли
            $group = new stdClass();
            $group->title = tr('Роли');
            $group->attr = array('class' => 'role');
            $group->group = TRUE;
            $this->options['roles'] = $group;
            
            // Вземаме всички роли
            $rQuery = core_Roles::getQuery();
            while($rec = $rQuery->fetch()) {
                $roleObj = new stdClass();
                $roleObj->title = $rec->role;
                $roleObj->id = $rec->id;
                $roleObj->value = self::getSysRoleId($rec->id);
                $this->options['r_' . $rec->id] = $roleObj;
            }
            
            // Ако има права за избор на цялата система, добавяме съответния избор
            if (haveRole($this->params['rolesForAllSysTeam'])) {
                
                $allSysTeam = self::getAllSysTeamId();
                
                $roleObj = new stdClass();
                $roleObj->title = tr("Цялата система");
                $roleObj->value = $allSysTeam;
                $roleObj->attr = array('clas' => 'all-sys-team');
                $this->options['r_' . 'allSysTeam'] = $roleObj;
            }
        }
        
        $this->options = parent::prepareOptions();
        
        return $this->options;
    }
    
    
    /**
     * 
     * @param string $value
     * 
     * @see type_User::toVerbal_()
     * 
     * @return string
     */
    function toVerbal_($value)
    {
        if ($value < 0) {
            $this->params['mvc'] = &cls::get('core_Roles');
            $this->params['select'] = 'role';
        }
        
        return parent::toVerbal_($value);
    }
    
    
    /**
     * 
     * @param string $value
     * 
     * @see type_User::fromVerbal_()
     * 
     * @return string
     */
    function fromVerbal_($value)
    {
        $key = self::getKeyFromTitle($value);
        
        if (!$key) {
            $key = $value;
        }
        
        list($type, $id) = explode('_', $key);
        
        if ($type == 'r') {
            $this->params['mvc'] = &cls::get('core_Roles');
            $this->params['select'] = 'role';
            
            $value = self::getSysRoleId($id);
        }
        
        return parent::fromVerbal_($value);
    }
    
    
    
    /**
     * 
     * 
     * @param string $value
     * 
     * @see type_User::fetchVal()
     * 
     * @return object
     */
    protected function fetchVal(&$value)
    {
        if ($value < 0) {
            $roleId = self::getRoleIdFromSys($value);
            $this->params['mvc'] = &cls::get('core_Roles');
            $this->params['select'] = 'role';
            
            return $this->params['mvc']->fetch((int)$roleId);
        }
        
        return parent::fetchVal($value);
    }
    
    
    /**
     * @see type_User::renderInput_()
     * 
     * @param string $name
     * @param string $value
     * @param array $attr
     * 
     * @return core_ET
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        if ($value < 0) {
            $value = self::getRoleIdFromSys($value);
            
            $this->params['mvc'] = &cls::get('core_Roles');
            $this->params['select'] = 'role';
            
            if ($value == 0) {
                $value = 'allSysTeam';
            }
            
            $value = 'r_' . $value;
        }
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Връща ID-то за allSysTeam
     * 
     * @return integer
     */
    public static function getAllSysTeamId()
    {
        static $allSysTeams = 0;
        
        if (!$allSysTeams) {
            $allSysTeams = 1-pow(2,31);
        }
        
        return $allSysTeams;
    }
    
    
    /**
     * Връща id за групата базирано на allSysTeam
     * 
     * @param integer $roleId
     * 
     * @return integer
     */
    public static function getSysRoleId($roleId)
    {
        $allSysTeam = self::getAllSysTeamId();
        
        $nRoleId = $allSysTeam + $roleId;
        
        return $nRoleId;
    }
    
    
    /**
     * Връща id на запис от модел core_Roles от id-то определено от getSysRoleId()
     * 
     * @param integer $sysRoleId
     * 
     * @return int|NULL
     */
    public static function getRoleIdFromSys($sysRoleId)
    {
        if ($sysRoleId >= 0) return NULL;
        
        $allSysTeam = self::getAllSysTeamId();
        
        $roleId = (int)($sysRoleId - $allSysTeam);
        
        return $roleId;
    }
    
    
    /**
     * Връща ключа на опциията за тази стойност
     * 
     * @param string|integer $userOrRole
     * 
     * @return NULL|string
     */
    public static function getOptVal($userOrRole)
    {
        if (strpos($userOrRole, '_')) return $userOrRole;
        
        if (!$userOrRole) return ;
        
        $inst = cls::get(get_called_class());
        $inst->prepareOptions();
        foreach ((array)$inst->options as $optVal => $vals) {
            if ($vals->value == $userOrRole) {
                
                return $optVal;
            }
        }
    }
}
