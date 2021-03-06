<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2019 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace   Splash\Local\Objects\Product;

use Splash\Core\SplashCore      as Splash;

/**
 * Dolibarr Products Stock Fields
 */
trait StockTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildStockFields()
    {
        global $langs;

        //====================================================================//
        // PRODUCT STOCKS
        //====================================================================//

        //====================================================================//
        // Stock Reel
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("stock_reel")
            ->Name($langs->trans("RealStock"))
            ->MicroData("http://schema.org/Offer", "inventoryLevel")
            ->isListed();

        //====================================================================//
        // Stock Alerte Level
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("seuil_stock_alerte")
            ->Name($langs->trans("StockLimit"))
            ->MicroData("http://schema.org/Offer", "inventoryAlertLevel");

        //====================================================================//
        // Stock Alerte Flag
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->Identifier("stock_alert_flag")
            ->Name($langs->trans("StockTooLow"))
            ->MicroData("http://schema.org/Offer", "inventoryAlertFlag")
            ->isReadOnly();

        //====================================================================//
        // Stock Expected Level
        $this->fieldsFactory()->create(SPL_T_INT)
            ->Identifier("desiredstock")
            ->Name($langs->trans("DesiredStock"))
            ->MicroData("http://schema.org/Offer", "inventoryTargetLevel");

        //====================================================================//
        // Average Purchase price value
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("pmp")
            ->Name($langs->trans("EstimatedStockValueShort"))
            ->MicroData("http://schema.org/Offer", "averagePrice")
            ->isReadOnly();
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getStockFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // STOCK INFORMATIONS
            //====================================================================//

            //====================================================================//
            // Stock Alerte Flag
            case 'stock_alert_flag':
                $this->out[$fieldName] = ($this->object->stock_reel < $this->object->seuil_stock_alerte);

                break;
            //====================================================================//
            // Stock Direct Reading
            case 'stock_reel':
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->getSimple($fieldName, "object", 0);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     */
    protected function setStockFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Writtings
            case 'stock_reel':
                $this->setProductStock($fieldData);

                break;
            //====================================================================//
            // Direct Writtings
            case 'seuil_stock_alerte':
            case 'desiredstock':
            case 'pmp':
                $this->setSimple($fieldName, $fieldData);

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Create Stock Transaction to Update Products Stocks Level
     *
     * @param int $newStock New Product Stock
     *
     * @return bool
     */
    private function setProductStock($newStock)
    {
        global $conf, $langs, $user;

        //====================================================================//
        // Compare Current Product Stock with new Value
        if ($this->object->stock_reel == $newStock) {
            return true;
        }
        //====================================================================//
        // Update Product Stock
        $delta = $this->object->stock_reel - $newStock;
        //====================================================================//
        // Verify Default Product Stock is defined
        if (empty($conf->global->SPLASH_STOCK)) {
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Product : No Local WareHouse Defined.");
        }
        //====================================================================//
        // Update Product Stock
        $result = $this->object->correct_stock(
            $user,                                      // Current User Object
            $conf->global->SPLASH_STOCK,                // Impacted Stock Id
            abs($delta),                                // Quantity to Move
            ($delta > 0)?1:0,                           // Direnction 0 = add, 1 = remove
            $langs->trans("Updated by Splash Module"),  // Operation Comment
            $this->object->price                        // Product Price for PMP
        );
        //====================================================================//
        // Check potential Errors
        if ($result < 0) {
            $this->catchDolibarrErrors();

            return false;
        }

        return true;
    }
}
