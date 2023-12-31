<?php

require_once "connection.model.php";

class ProcessosModel
{

    // static public function mdlGetDataProcessos()
    // {

    //     $stmt = Connection::connect()->prepare("SELECT * FROM processos");
    //     $stmt->execute();

    //     return $stmt->fetchAll(PDO::FETCH_OBJ);
    // }

    static public function mdlListarProcessos()
    {

        $stmt = Connection::connect()->prepare('call prc_listarProcessosFaseInterna');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    static public function mdlRegistrarProcessos($idNup, $selProcesso, $idNrProcesso, $selRequisitante, $selFase, $idDescricaoResumida, $idDescricaoDetalhada, $idDataEntrada)
    {
        try {
            $stmt = Connection::connect()->prepare("INSERT INTO PROCESSOS(
                NUP, 
                tipo_processo_origem, 
                numero_processo_origem, 
                requisitante, 
                situacao, 
                assunto_objeto, 
                descricao_detalhada_objeto,
                data_entrada)

            VALUES(
                :idNup, 
                :selProcesso, 
                :idNrProcesso,
                :selRequisitante, 
                :selFase, 
                :idDescricaoResumida, 
                :idDescricaoDetalhada,
                :idDataEntrada)");


            $stmt->bindParam(":idNup", $idNup, PDO::PARAM_STR);
            $stmt->bindParam(":selProcesso", $selProcesso, PDO::PARAM_STR);
            $stmt->bindParam(":idNrProcesso", $idNrProcesso, PDO::PARAM_STR);
            $stmt->bindParam(":selRequisitante", $selRequisitante, PDO::PARAM_STR);
            $stmt->bindParam(":selFase", $selFase, PDO::PARAM_STR);
            $stmt->bindParam(":idDescricaoResumida", $idDescricaoResumida, PDO::PARAM_STR);
            $stmt->bindParam(":idDescricaoDetalhada", $idDescricaoDetalhada, PDO::PARAM_STR);
            $stmt->bindParam(":idDataEntrada", $idDataEntrada, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $response = "ok";
            } else {
                $response = "Error";
            }
        } catch (Exception $e) {
            $response = 'Exception capturada:' . $e->getMessage();
        }

        return $response;
        $stmt = null;
    }

    static public function mdlEditarProcessos($table, $data, $id, $nameId)
    {

        $set = "";

        foreach ($data as $key => $value) {

            $set .= $key . " = :" . $key . ",";
        }

        $set = substr($set, 0, -1);

        $stmt = Connection::connect()->prepare("UPDATE $table SET $set WHERE $nameId = :$nameId");

        foreach ($data as $key => $value) {

            $stmt->bindParam(":" . $key, $data[$key], PDO::PARAM_STR);
        }

        $stmt->bindParam(":" . $nameId, $id, PDO::PARAM_INT);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return Connection::connect()->errorInfo();
        }
    }

    static public function mdlDeletarProcesso($table, $id, $nameId)
    {

        try {
            $historico_data_entrada_saida = "historico_data_entrada_saida";

            $stmt = Connection::connect()->prepare("DELETE FROM $historico_data_entrada_saida WHERE $nameId = :$nameId");

            $stmt->bindParam(":" . $nameId,  $id,  PDO::PARAM_INT);

            $stmt->execute();
        } catch (Exception $e) {
            $response = 'Exception capturada no mdlDeletar:' . $e->getMessage();
        }



        $stmt = Connection::connect()->prepare("DELETE FROM $table WHERE $nameId = :$nameId");

        $stmt->bindParam(":" . $nameId,  $id,  PDO::PARAM_INT);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return Connection::connect()->errorInfo();
        }
    }

    static public function mdlRegistrarTarefasKanban($rowId, $cardData)
    {

        
        $conn = Connection::connect();

        $stmt = $conn->prepare("SELECT * FROM kanban_tasks WHERE id_processo = :rowId");
        $stmt->bindParam(":rowId", $rowId, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $diferentesDeletar = array();


        foreach ($resultado as $linhaback) {

            $diferentes = false;

            foreach ($cardData as $linhaFront) {
                if ($linhaback['position'] == $linhaFront['index'] &&  $linhaback['columnKanban'] == $linhaFront['column'] && $linhaback['id_processo'] == $rowId) {
                    $diferentes = true;
                    break;
                }
            }
            if (!$diferentes) {
                $diferentesDeletar[] = $linhaback;
            }
        }

        foreach ($diferentesDeletar as $diferente) {
            $stmt = $conn->prepare("DELETE FROM kanban_tasks WHERE id_processo = :rowId AND position = :position AND columnKanban = :columnKanban");
            $stmt->bindParam(":rowId", $rowId, PDO::PARAM_INT);
            $stmt->bindParam(":position", $diferente['position'], PDO::PARAM_INT);
            $stmt->bindParam(":columnKanban", $diferente['columnKanban'], PDO::PARAM_STR);
            $stmt->execute();
        }

        try {
            foreach ($cardData as $card) {

                $title = $card['title'];
                $description = $card['description'];
                $column = $card['column'];
                $index = $card['index'];

                $conn = Connection::connect();

                $stmt = $conn->prepare("SELECT * FROM kanban_tasks WHERE id_processo = :rowId AND columnKanban = :column AND position != :index");
                $stmt->bindParam(":rowId", $rowId, PDO::PARAM_INT);
                $stmt->bindParam(":column", $column, PDO::PARAM_STR);
                $stmt->bindParam(":index", $index, PDO::PARAM_INT);
                $stmt->execute();
                $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);


                $stmt = $conn->prepare("SELECT * FROM kanban_tasks WHERE id_processo = :rowId AND columnKanban = :column AND position = :index");
                $stmt->bindParam(":rowId", $rowId, PDO::PARAM_INT);
                $stmt->bindParam(":column", $column, PDO::PARAM_STR);
                $stmt->bindParam(":index", $index, PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($result) > 0) {

                    // Atualizar tarefas existentes
                    $stmt = $conn->prepare("UPDATE kanban_tasks SET title = :title, description = :description WHERE id_processo = :rowId AND columnKanban = :column AND position = :index");
                    $response = "ok"; // Suponhamos que tudo correu bem

                    $stmt->bindParam(":rowId", $rowId, PDO::PARAM_INT);
                    $stmt->bindParam(":title", $title, PDO::PARAM_STR);
                    $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                    $stmt->bindParam(":column", $column, PDO::PARAM_STR);
                    $stmt->bindParam(":index", $index, PDO::PARAM_INT);

                    if (!$stmt->execute()) {
                        return "erro no mdlRegistrarTarefasKanban";
                        return Connection::connect()->errorInfo();
                    }
                } else {

                    // Cadastrar tarefas
                    $stmt = $conn->prepare("INSERT INTO kanban_tasks (id_processo, title, description, columnKanban, position) VALUES (:rowId, :title, :description, :column, :index)");
                    $response = "ok"; // Suponhamos que tudo correu bem

                    $stmt->bindParam(":rowId", $rowId, PDO::PARAM_INT);
                    $stmt->bindParam(":title", $title, PDO::PARAM_STR);
                    $stmt->bindParam(":description", $description, PDO::PARAM_STR);
                    $stmt->bindParam(":column", $column, PDO::PARAM_STR);
                    $stmt->bindParam(":index", $index, PDO::PARAM_INT);

                    if (!$stmt->execute()) {
                        return "erro no mdlRegistrarTarefasKanban";
                        return Connection::connect()->errorInfo();
                    }
                }
            }
            return $response;
        } catch (Exception $e) {
            return 'Exception capturada:' . $e->getMessage();
        } finally {
            $stmt = null;
            $conn = null;
        }
    }

    static public function mdlLimparTodosCardsKanban($rowId)
    {
        $stmt = Connection::connect()->prepare("DELETE FROM kanban_tasks WHERE id_processo = :$rowId");

        $stmt->bindParam(":" . $rowId,  $rowId,  PDO::PARAM_INT);

        if ($stmt->execute()) {

            return "ok";
        } else {

            return Connection::connect()->errorInfo();
        }
    }

    static public function mdlDeletarUnicoCardKanban($rowId, $column, $index)
    {


        $stmt = Connection::connect()->prepare("DELETE FROM kanban_tasks WHERE id_processo = :id_processo AND columnKanban = :columnKanban AND position = :position");

        $stmt->bindParam(":id_processo", $rowId, PDO::PARAM_INT);
        $stmt->bindParam(":columnKanban", $column, PDO::PARAM_STR);
        $stmt->bindParam(":position", $index, PDO::PARAM_INT);



        if ($stmt->execute()) {
            return "ok";
        } else {
            return Connection::connect()->errorInfo();
        }
    }

    static public function mdlGetKanbanTasks()
    {

        $stmt = Connection::connect()->prepare("SELECT * FROM kanban_tasks");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
