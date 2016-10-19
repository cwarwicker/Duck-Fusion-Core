<?php

namespace DF\%ns%;

/**
 * Setting class
 * 
 * @copyright 21-Jul-2013
 * @package DuckFusion
 * @version 1
 * @author Conn Warwicker <conn@cmrwarwicker.com>
 *
 */
class Setting {
    
    const useSettings = true;
    const table = 'settings';
    const setting_field = 'setting';
    const value_field = 'value';
    const id_field = 'ID';
    
    /**
     * Get the value of a setting
     * @global type $db
     * @param type $setting
     * @return type
     */
    public static function getSetting($setting){
        
        if (self::useSettings)
        {
        
            global $db;

            $setting = $db->select(self::table, array(self::setting_field => $setting));

            $val = self::value_field;

            return ($setting) ? $setting->$val : false;
        
        }
        
    }
    
    /**
     * Set the value of a setting
     * @param type $setting
     * @param type $value
     */
    public static function setSetting($setting, $value){
        
        if (self::useSettings)
        {
            
            global $db;

            $check = $db->select(self::table, array(self::setting_field => $setting));

            // Exists, so update value
            if ($check){

                $id = self::id_field;
                return $db->update(self::table, array(self::value_field => $value), array(self::id_field => $check->$id));

            } else {

                // Doesn't exist, so insert
                return $db->insert(self::table, array(self::setting_field => $setting, self::value_field => $value));

            }
        
        }
        
    }
    
}
