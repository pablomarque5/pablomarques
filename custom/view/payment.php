<?php
  $user = Structure::verifySession();
  Structure::header();
  
  $idTransaction = $_POST['id_transaction'];
  $paymentMethod = $_POST['payment_method'];

  $genericDAO = new GenericDAO;
  $transaction = $genericDAO->selectAll("Transaction", "id = $idTransaction");
 ?>
 
          <main>
            <header class="center">
                <h1>Escolha o método de pagamento</h1>
            </header>
            <section class="wrapper">
<?

  $payment = new Payment;

  if ($metodo_pagamento == "BOL") :
      if (!PAY_BOLETO) {
          Structure::redirWithMessage("Erro 305\nO metodo de pagamento BOLETO nao esta disponivel.", "/dashboard"); //TODO: Adicionar acento
      }
      
      $pagamento = $payment->pay($usuario->get('id'), $transacao->get('id'), $metodo_pagamento, $valor_final);
      Structure::header();
      echo "<h1>Boleto</h1>";
      echo '<a target="_blank" href="'.$pagamento->get('info').'">Clique aqui para imprimir seu boleto</a>';
      echo "<h3>Guarde sempre o seu comprovante de pagamento.</h3>";
      Structure::footer();

  elseif ($metodo_pagamento == "DEP") :
      if (!PAY_DEPOSITO) {
          Structure::redirWithMessage("Erro 305\nO metodo de pagamento DEPOSITO nao esta disponivel.", "/dashboard"); //TODO: Adicionar acento
      }
      
      $pagamento = $payment->pay($usuario->get('id'), $transacao->get('id'), $metodo_pagamento, $valor_final);
      if ($pagamento) {
          Structure::header();
          echo "<h1>Depósito</h1>";
          $html = "";
          $html .= "<p><strong>Você escolheu pagar utilizando Depósito Bancário. Por favor, realize o depósito em até 4 dias úteis para a conta abaixo e envie o comprovante para <em>".DEPOSITO_EMAIL."</em>.</strong></h2>";
          $html .= "<h2>Dados Bancários</h2>";
          $html .= "<p>".DEPOSITO_BANCO."<br />";
          $html .= DEPOSITO_NOME."<br />";
          $html .= "CPF ".DEPOSITO_CPF."<br />";
          $html .= "Agência ".DEPOSITO_AGENCIA."<br />";
          $html .= "Conta ".DEPOSITO_CONTA."<br />";
          $html .= '<h3>Valor Total: R$ '.$valor_final.'</h3>';
          echo $html;
          echo "<h3>Guarde sempre o seu comprovante de pagamento.</h3>";
          Structure::footer();
      } else {
          Structure::redirWithMessage("Erro 306\nProblemas ao processar seu pagamento. Tente novamente mais tarde. Sua inscricao esta garantida.", "/dashboard"); //TODO: Adicionar acento
      }

  elseif ($metodo_pagamento == "PPL") :
      if (!PAY_PAYPAL) {
          Structure::redirWithMessage("Erro 305\nO metodo de pagamento PAYPAL nao esta disponivel.", "/dashboard"); //TODO: Adicionar acento
      }

      $pagamento = $payment->pay($usuario->get('id'), $transacao->get('id'), $metodo_pagamento, $valor_final);
      Structure::header();
      $paypal_html = '<h2>PayPal</h2>';
      $paypal_html .= '<h2>Clique no botão abaixo para realizar o pagamento de sua inscrição.</h2>';
      $paypal_html .= '<h3>Utilize o mesmo e-mail que você utilizou em seu cadastro.</h3>';
      $paypal_html .= '<h3>'.PAYPAL_ITEM_NAME.'</h3>';
      $paypal_html .= '<h3>Valor Total: R$ '.$valor_final.'</h3>';
      echo $paypal_html;
      echo $pagamento->get('obs');
      Structure::footer();


  elseif ($metodo_pagamento == "PGS") :
      if (!PAY_PAGSEGURO) {
          Structure::redirWithMessage("Erro 305\nO metodo de pagamento PAGSEGURO nao esta disponivel.", "/dashboard"); //TODO: Adicionar acento
      }

      $transactionPayment = $payment->pay($user->get('id'), $transaction->get('id'), $paymentMethod, $transaction->get('total_value'));

      if ($pagamento) {
          Structure::header();
          $html = '<h1>PagSeguro</h1>';
          $html .= '<h2>Clique no link abaixo para realizar o pagamento de sua inscrição.</h2>';
          $html .= '<h3>Utilize o mesmo e-mail que você utilizou em seu cadastro.</h3>';
          $html .= '<h3>Valor Total Final: R$ '.($valor_final * PAGSEGURO_MULTIPLIER).'</h3>';
          $html .= "<p><a href='".$pagamento->get('info')."'>PagSeguro</a></p>";
          echo $html;
          Structure::footer();
      } else {
          Structure::redirWithMessage("Erro 306\nProblemas ao processar seu pagamento. Tente novamente mais tarde. Sua inscricao esta garantida.", "/dashboard"); //TODO: Adicionar acento
      }
  endif;
?>