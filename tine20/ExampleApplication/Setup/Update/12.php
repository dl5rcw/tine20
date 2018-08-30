<?php

/**
 * Tine 2.0
 *
 * @package     ExampleApplication
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class ExampleApplication_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = 'release012::update001';
    const RELEASE012_UPDATE002 = 'release012::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
    ];


    // gets executed 2nd
    public function update001()
    {
        /** app version 12.0 should be of course 12.1 or similar */
        $this->addApplicationUpdate('ExampleApplication', '12.0', self::RELEASE012_UPDATE001);
    }

    // gets executed first
    public function update002()
    {
        /** app version 12.0 should be of course 12.2 or similar */
        $this->addApplicationUpdate('ExampleApplication', '12.0', self::RELEASE012_UPDATE002);
    }
}