<?php

function isMaxReached() {
    $isClosed = true;
    $genericDAO = new GenericDAO;

   //if (getUserExemptions($_COOKIE['user_id']) || $genericDAO->selectAll("RespostaEdital", "status = 1 AND id_user = ".$_COOKIE['user_id'])) return false;

    $genericDAO = new GenericDAO;
    $now = date('Y-m-d H:i:s');

    $productsFatherStr = "";
    $productsFather = $genericDAO->selectAll("ProductFather", NULL);
    if ($productsFather) {
        if (!is_array($productsFather)) $productsFather = array($productsFather);
        foreach ($productsFather as $productFather) {
            if (!$genericDAO->selectAll("ProductFather", "id_product = {$productFather->get('id_father')}")) {
                if (strlen($productsFatherStr) > 0) $productsFatherStr .= ", ";
                $productsFatherStr .= $productFather->get('id_father');
            }
        }
    }
    
    $fatherProducts = $genericDAO->selectAll("Product", "dt_begin < '$now' AND dt_end > '$now'".(strlen($productsFatherStr) > 0 ? " AND id IN ($productsFatherStr)" : ""));
    if (!is_array($fatherProducts)) $fatherProducts = array($fatherProducts);
    foreach ($fatherProducts as $product) {
        $max = $product->get('max_quantity');
        $count = 0;
        $transactionItems = $genericDAO->selectAll("TransactionItem", "id_product = ".$product->get('id'));
        if ($transactionItems) {
            if (!is_array($transactionItems)) $transactionItems = array($transactionItems);
            $inStr = "";
            foreach ($transactionItems as $transactionItem) {
                if (strlen($inStr) > 0) $inStr .= ', ';
                $inStr .= $transactionItem->get('id_transaction');
            }

            $transactionCount = $genericDAO->selectCount("Transaction", "id", "value_exemption = 0 && status <> 3 AND id IN ($inStr)");
            $count = $transactionCount;
        }
        if ($count < $max) {
            $isClosed = false;
        }
    }

    return $isClosed;
}

function isMaxReachedByProd($idProduct) {

  //if (getUserExemptions($_COOKIE['user_id']) || $genericDAO->selectAll("RespostaEdital", "status = 1 AND id_user = ".$_COOKIE['user_id'])) return false;

  $isClosed = true;
  $now = date('Y-m-d H:i:s');
  $genericDAO = new GenericDAO;

  $product = $genericDAO->selectAll("Product", "id = $idProduct AND dt_begin < '$now' AND dt_end > '$now'");
  if ($product) {
    if (!is_array($product)) {
      $max = $product->get('max_quantity');
      $count = 0;
      $transactionItems = $genericDAO->selectAll("TransactionItem", "id_product = ".$product->get('id'));
      if ($transactionItems) {
        if (!is_array($transactionItems)) $transactionItems = array($transactionItems);
        $inStr = "";
        foreach ($transactionItems as $transactionItem) {
            if (strlen($inStr) > 0) $inStr .= ', ';
            $inStr .= $transactionItem->get('id_transaction');
        }

        $transactionCount = $genericDAO->selectCount("Transaction", "id", "value_exemption = 0 && status <> 3 AND id IN ($inStr)");
        $count = $transactionCount;
      }
      echo "\n<!-- $idProduct:$count/$max -->";
      if ($count < $max) {
        $isClosed = false;
      }

    }
  }

  return $isClosed;
}

function userHasProduct($idUser, $idProduct) {
    $genericDAO = new GenericDAO;
    $transactions = $genericDAO->selectAll("Transaction", "id_user = $idUser AND (status <> 3)");
    if ($transactions) {
        if (!is_array($transactions)) $transactions = array($transactions);
        foreach ($transactions as $transaction) {
            $transactionsItems = $genericDAO->selectAll("TransactionItem", "id_product = $idProduct AND id_transaction = ".$transaction->get('id'));
            if ($transactionsItems) return true;
        }
    }

    return false;
}

function sumTotalExemptions($exemptions) {
    $genericDAO = new GenericDAO;
    if ($exemptions && $exemptions !== true) {
        if (!is_array($exemptions)) $exemptions = array($exemptions);
        $totalValue = 0;
        foreach ($exemptions as $exemption) {
            $product = $genericDAO->selectAll("Product", "id = ".$exemption->get('id_product'));
            if ($product) {
                $totalValue += floatval($product->get('price')) * floatval($exemption->get('modifier'));
            }
        }
        if ($totalValue > 0) return $totalValue;
    }
    return false;
}

function getTotalValueExemptions($idUser) {
    $exemptions = getUserExemptions($idUser);
    $total = sumTotalExemptions($exemptions);
    if ($total && $total > 0) return $total;
    return false;
}

function getUserExemptions($idUser) {
    $genericDAO = new GenericDAO;

    $user = $genericDAO->selectAll("User", "id = $idUser");

    $exemptionEmails = $genericDAO->selectAll("ExemptionEmail", "email = '".$user->get('email')."'");
    if ($exemptionEmails) {
        if (!is_array($exemptionEmails)) $exemptionEmails = array($exemptionEmails);
        return $exemptionEmails;
    }

    $selectedEditals = $genericDAO->selectAll("RespostaEdital", "id_user = $idUser AND status = 1");
    if ($selectedEditals) {
        if (!is_array($selectedEditals)) $selectedEditals = array($selectedEditals);

        $highestPackExemption = false;
        $highestValueExemption = 0;
        $selectedEditalsStr = "";
        foreach ($selectedEditals as $selectedEdital) {
            $edital = $genericDAO->selectAll("Edital", "id = ".$selectedEdital->get('id_edital'));
            if ($edital && sizeof($edital) > 0) {
                $exemptions = $genericDAO->selectAll("Exemption", "id_edital = ".$edital->get('id'));
                $totalValue = sumTotalExemptions($exemptions);
                if ($totalValue && $totalValue > $highestValueExemption) {
                    $highestValueExemption = $totalValue;
                    $highestPackExemption = $exemptions;
                }


                // if ($exemptions) {
                //     if (!is_array($exemptions)) $exemptions = array($exemptions);
                //     $totalValue = 0;
                //     foreach ($exemptions as $exemption) {
                //         $product = $genericDAO->selectAll("Product", "id = ".$exemption->get('id_product'));
                //         if ($product) {
                //             $totalValue += floatval($product->get('price')) * floatval($exemption->get('modifier'));
                //         }
                //     }
                //     if ($totalValue > $highestValueExemption) {
                //         $highestValueExemption = $totalValue;
                //         $highestPackExemption = $exemptions;
                //     }
                // }
            }
        }

        if ($highestPackExemption) return $highestPackExemption;
    }

    if ($genericDAO->selectAll("RespostaEdital", "status = 1 AND id_user = $idUser")) return true;

    return false;
}

function userHasExemption($idUser, $idProduct) {
    $genericDAO = new GenericDAO;
    $exemptions = getUserExemptions($idUser);
    if ($exemptions && $exemptions !== true) {
        if (!is_array($exemptions)) $exemptions = array($exemptions);
        foreach ($exemptions as $exemption) {
            if ($exemption->get('id_product') == $idProduct) return $exemption;
        }
    }

    return false;
}

?>
