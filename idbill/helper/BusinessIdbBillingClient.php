<?php
# * ********************************************************************* *
# *                                                                       *
# *   Driver for IDBill                                                   *
# *   This file is part of idbill. This project may be found at:          *
# *   https://github.com/IdentityBank/Php_idbill.                         *
# *                                                                       *
# *   Copyright (C) 2020 by Identity Bank. All Rights Reserved.           *
# *   https://www.identitybank.eu - You belong to you                     *
# *                                                                       *
# *   This program is free software: you can redistribute it and/or       *
# *   modify it under the terms of the GNU Affero General Public          *
# *   License as published by the Free Software Foundation, either        *
# *   version 3 of the License, or (at your option) any later version.    *
# *                                                                       *
# *   This program is distributed in the hope that it will be useful,     *
# *   but WITHOUT ANY WARRANTY; without even the implied warranty of      *
# *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the        *
# *   GNU Affero General Public License for more details.                 *
# *                                                                       *
# *   You should have received a copy of the GNU Affero General Public    *
# *   License along with this program. If not, see                        *
# *   https://www.gnu.org/licenses/.                                      *
# *                                                                       *
# * ********************************************************************* *

################################################################################
# Namespace                                                                    #
################################################################################

namespace idb\idbill;

################################################################################
# Use(s)                                                                       #
################################################################################

use Exception;
use yii\helpers\ArrayHelper;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class BusinessIdbBillingClient
 *
 * @package idb\idbill
 */
class BusinessIdbBillingClient extends IdbBillingClient
{

    /**
     * @param $action
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function findCostByAction($action)
    {
        $query = [
            'query' => 'findActionsCosts',
            "FilterExpression" => [
                'o' => '=',
                'l' => '#col',
                'r' => ':col'
            ],
            "ExpressionAttributeNames" => ['#col' => 'action_name'],
            "ExpressionAttributeValues" => [':col' => $action]
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function findAllCosts()
    {
        $query = [
            'query' => 'findActionsCosts',
            "FilterExpression" => [
                'o' => '>',
                'l' => '#col',
                'r' => ':col'
            ],
            "ExpressionAttributeNames" => ['#col' => 'id'],
            "ExpressionAttributeValues" => [':col' => 0]
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function findAllBusinessPackages()
    {
        $query = [
            'query' => 'findCountAllBusinessPackage'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $response
     *
     * @return |null
     * @throws \Exception
     */
    public function parseResponse($response)
    {
        if ($response == null) {
            throw new Exception('Query returned null');
        }

        $response = json_decode($response, true);

        switch ($response['statusCode']) {
            case 200:
                $response = json_decode($response['result'], true);

                return ArrayHelper::getValue($response, 'QueryData', true);
            default:
                return null;
        }
    }

    /**
     * @param $billingName
     *
     * @return \idb\idbill\IdbBillingClient|null
     */
    public static function model($billingName)
    {
        if (!empty($billingName)) {
            return new self($billingName);
        }

        return null;
    }

    /**
     * @param $query
     *
     * @return bool|string|null
     */
    protected function execute($query)
    {
        $data = base64_encode(json_encode($query));
        exec("idbconsole idb-task/take-credits '$data'");

        return parent::execute($query);
    }
}

################################################################################
#                                End of file                                   #
################################################################################
