<?php
    /**
     * Created by PhpStorm.
     * User: gerasart
     * Date: 06.06.2020
     * Time: 01:18
     */
    
    namespace Foks\Interfaces;
    
    interface FoksData {
        
        
        /**
         * @return mixed
         */
        public static function getProducts();
        
        
        /**
         * @return mixed
         */
        public static function getCategories();
    }
