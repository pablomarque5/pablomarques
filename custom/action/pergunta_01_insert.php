<?php
    $usuario = Structure::verifySession();
    $genericDAO = new GenericDAO;
    $return = array();

    $pergunta = DataBinder::bind($_POST, "Pergunta");

    if ($genericDAO->insert($pergunta)) :
        $return[] = array(
            "Action" => "Message",
            "Message" => "Pergunta cadastrada com sucesso. <a href=\"".APP_URL."/admin/pergunta?edital=".$pergunta->get('id_edital')."\">Cadastre uma nova</a> ou <a href=\"".APP_URL."/admin/pergunta/action/list\">Veja todas.</a>"
        );
    else :
        $return[] = array(
            "Action" => "Error",
            "Error" => "Problemas ocorreram ao cadastrar pergunta."
        );
    endif;

    echo json_encode($return);
?>
