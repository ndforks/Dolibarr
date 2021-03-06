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
 * Dolibarr Products Main Fields
 */
trait MainTrait
{
    /**
     * Build Address Fields using FieldFactory
     */
    protected function buildMainFields()
    {
        global $langs;

        //====================================================================//
        // PRODUCT SPECIFICATIONS
        //====================================================================//

        //====================================================================//
        // Weight
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("weight")
            ->Name($langs->trans("Weight"))
            ->Description($langs->trans("Weight")."(".$langs->trans("WeightUnitkg").")")
            ->MicroData("http://schema.org/Product", "weight");

        //====================================================================//
        // Lenght
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("length")
            ->Name($langs->trans("Length"))
            ->Description($langs->trans("Length")."(".$langs->trans("LengthUnitm").")")
            ->MicroData("http://schema.org/Product", "depth");

        //====================================================================//
        // Surface
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("surface")
            ->Name($langs->trans("Surface"))
            ->Description($langs->trans("Surface")."(".$langs->trans("SurfaceUnitm2").")")
            ->MicroData("http://schema.org/Product", "surface");

        //====================================================================//
        // Volume
        $this->fieldsFactory()->create(SPL_T_DOUBLE)
            ->Identifier("volume")
            ->Name($langs->trans("Volume"))
            ->Description($langs->trans("Volume")."(".$langs->trans("VolumeUnitm3").")")
            ->MicroData("http://schema.org/Product", "volume");
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     */
    protected function getMainFields($key, $fieldName)
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                $this->out[$fieldName] = (float) $this->convertWeight(
                    $this->object->weight,
                    $this->object->weight_units
                );

                break;
            case 'length':
                $this->out[$fieldName] = (float) $this->convertLength(
                    $this->object->length,
                    $this->object->length_units
                );

                break;
            case 'surface':
                $this->out[$fieldName] = (float) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                );

                break;
            case 'volume':
                $this->out[$fieldName] = (float) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                );

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
    protected function setMainFields($fieldName, $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // PRODUCT SPECIFICATIONS
            //====================================================================//
            case 'weight':
                $this->updateProductWeight($fieldData);

                break;
            case 'length':
                if ((string)$fieldData !== (string) $this->convertLength(
                    $this->object->length,
                    $this->object->length_units
                )) {
                    $nomalized = $this->normalizeLength($fieldData);
                    $this->object->length = $nomalized->length;
                    $this->object->length_units = $nomalized->length_units;
                    $this->needUpdate();
                }

                break;
            case 'surface':
                if ((string)$fieldData !== (string) $this->convertSurface(
                    $this->object->surface,
                    $this->object->surface_units
                )) {
                    $nomalized = $this->normalizeSurface($fieldData);
                    $this->object->surface = $nomalized->surface;
                    $this->object->surface_units = $nomalized->surface_units;
                    $this->needUpdate();
                }

                break;
            case 'volume':
                if ((string)$fieldData !== (string) $this->convertVolume(
                    $this->object->volume,
                    $this->object->volume_units
                )) {
                    $nomalized = $this->normalizeVolume($fieldData);
                    $this->object->volume = $nomalized->volume;
                    $this->object->volume_units = $nomalized->volume_units;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Update Product Weight with Variants Management
     *
     * @param float $fieldData
     */
    private function updateProductWeight($fieldData)
    {
        //====================================================================//
        // Check if Product Weight Updated
        $weightStr = $this->convertWeight($this->object->weight, $this->object->weight_units);
        if ((string) $fieldData == (string) $weightStr) {
            return;
        }
        //====================================================================//
        // Update Current Product Weight
        $nomalized = $this->normalizeWeight($fieldData);
        $this->object->weight = $nomalized->weight;
        $this->object->weight_units = $nomalized->weight_units;
        $this->needUpdate();
        //====================================================================//
        // Update Current Product Weight
        if ($this->isVariant() && !empty($this->baseProduct)) {
            // Update Unit on Base Product
            $this->setSimple("weight_units", $nomalized->weight_units, "baseProduct");
            // Update Combination
            $this->setSimple(
                "variation_weight",
                $nomalized->weight - $this->baseProduct->weight,
                "combination"
            );

            return;
        }
    }
}
