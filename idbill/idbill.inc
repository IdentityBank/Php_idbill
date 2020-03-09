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
# Define(s)                                                                    #
################################################################################

define('IDB_BILL_ROOT_PATH', dirname(__FILE__));

################################################################################
# Include(s)                                                                   #
################################################################################

include_once 'idbill/idbill/helper/IdbBillingClient.php';
include_once 'idbill/idbill/helper/BusinessIdbBillingClient.php';
include_once 'idbill/idbill/helper/BillingIdbBillingClient.php';

################################################################################
#                                End of file                                   #
################################################################################
