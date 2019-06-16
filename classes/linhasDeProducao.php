<?php
class LinhasDeProducao{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getLinhasDeProducao($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'l.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select l.id, l.nome, l.ativo, s.id id_setor, s.nome nome_setor, ls.ordem
            from pcp_linhas_de_producao l
            join pcp_linhas_de_producao_setores ls on ls.id_linha_de_producao = l.id
            join pcp_setores s on s.id = ls.id_setor
            '.$where.'
            order by l.id, ls.ordem;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $request['id']);
        $stmt->execute($filters);

        $responseData = array();
        $subprodutos = array();
        $prevId = 0;
        $i = 0;
        while($row = $stmt->fetch()){
            // Primeira linha
            if($i === 0){
                $responseData[] = array(
                    'id' => (int)$row->id,
                    'nome' => $row->nome,
                    'ativo' => $row->ativo
                );
                $i++;
            }
            // Demais linhas
            else{
                if($prevId != (int)$row->id){
                    $responseData[($i - 1)]['setores'] = $setores;
                    $setores = [];
                    $responseData[] = array(
                        'id' => (int)$row->id,
                        'nome' => $row->nome,
                        'ativo' => $row->ativo
                    );
                    $i++;
                }
            }
            if($row->id_setor !== null){
                $setores[] = array(
                    'id' => (int)$row->id_setor,
                    'nome' => $row->nome_setor,
                    'ordem' => (int)$row->ordem
                );
            }

            $prevId = $row->id;
        }
        $responseData[($i - 1)]['setores'] = $setores;

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateLinhaDeProducao($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_linhas_de_producao
                    set
                        nome = :nome,
                        ativo = :ativo
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();
                $linhaDeProducaoId = $request['id'];
                $msg = 'Linha de producao atualizada com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_linhas_de_producao
                    set
                        nome = :nome,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();
                $linhaDeProducaoId = $this->pdo->lastInsertId();
                $msg = 'Linha de produção cadastrada com sucesso.';
            }

            // Relação linha_de_producao x setor
            if($request['setores'] !== null and count($request['setores']) > 0){
                $sql = '
                    delete from pcp_linhas_de_producao_setores
                    where id_linha_de_producao = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $linhaDeProducaoId);
                $stmt->execute();

                foreach($request['setores'] as $key => $setor){
                    $sql = '
                        insert into pcp_linhas_de_producao_setores
                        set
                            id_linha_de_producao = :id_linha_de_producao,
                            id_setor = :id_setor,
                            ordem = :ordem;
                    ';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_linha_de_producao', $linhaDeProducaoId);
                    $stmt->bindParam(':id_setor', $setor['id']);
                    $stmt->bindParam(':ordem', $setor['ordem']);
                    $stmt->execute();
                }
            }

            // Reponse
            return json_encode(array(
                'success' => true,
                'msg' => $msg
            ));
        }catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function deleteLinhaDeProducao($filters){
        try{
            $sql = '
                delete from pcp_linhas_de_producao
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            $sql = '
                delete from pcp_linhas_de_producao_setores
                where id_linha_de_producao = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Linha de producao removida com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function getSetoresPorLinhaDeProducao($filters){
        $sql = '
            select lps.id_linha_de_producao id, lp.nome nome, lps.id_setor, s.nome nome_setor, lps.ordem
            from pcp_linhas_de_producao_setores lps
            join pcp_linhas_de_producao lp on lp.id = lps.id_linha_de_producao
            join pcp_setores s on s.id = lps.id_setor
            where lps.id_linha_de_producao = :id
            order by lps.ordem;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $filters['id']);
        $stmt->execute($filters);

        $setores = array();
        $prevId = 0;
        $i = 0;
        while($row = $stmt->fetch()){
            // Primeira linha
            if($i === 0){
                $responseData = array(
                    'id' => (int)$row->id,
                    'nome' => $row->nome
                );
            }

            $setores[] = array(
                'id' => $row->id_setor,
                'nome' => $row->nome_setor,
                'ordem' => $row->ordem
            );
            $i++;
        }
        $responseData['setores'] = $setores;


        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }
}