<?php

class Perfis{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getPerfis($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'p.'.$key.' = :'.$key;
                $i++;
            }
        }
        /*
        $sql = '
            select p.id, p.nome, p.administrativo, p.ativo
            from pcp_perfis p
            '.$where.'
            order by id;
        ';
        */
        $sql = '
            select p.id, p.nome, p.administrativo, p.ativo, ps.id_setor, s.nome nome_setor, lps.ordem
            from pcp_perfis p
            left JOIN pcp_perfis_setores ps ON ps.id_perfil = p.id
            left JOIN pcp_setores s ON s.id = ps.id_setor
            left join pcp_linhas_de_producao_setores lps on lps.id_setor = ps.id_setor
            group by ps.id_setor
            order BY p.id, lps.ordem;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        $setores = array();
        $prevId = 0;
        $i = 0;
        while ($row = $stmt->fetch()) {
            // Primeira linha
            if($i === 0){
                $responseData[] = array(
                    'id' => (int)$row->id,
                    'nome' => $row->nome,
                    'administrativo' => $row->administrativo,
                    'ativo' => $row->ativo
                );
                $i++;
            }
            // Demais linhas
            else{
                if($prevId != (int)$row->id){
                    $responseData[($i - 1)]['subprodutos'] = $setores;
                    $setores = [];
                    $responseData[] = array(
                        'id' => (int)$row->id,
                        'nome' => $row->nome,
                        'administrativo' => $row->administrativo,
                        'ativo' => $row->ativo
                    );
                    $i++;
                }
            }
            if($row->id_setor !== null){
                $setores[] = array(
                    'id' => (int)$row->id_setor,
                    'nome' => $row->nome_setor
                );
            }
            $prevId = $row->id;
        }
        $responseData[($i - 1)]['setores'] = $setores;

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
        /*
        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'administrativo' => $row->administrativo,
                'ativo' => $row->ativo
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
        */
    }

    public function createUpdatePerfil($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('administrativo', $request) or $request['administrativo'] === '' or $request['administrativo'] === null)
                throw new \Exception('Campo Administrativo é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if($request['administrativo'] === 'N' and (!array_key_exists('setores', $request) or count($request['setores']) <= 0 or $request['setores'] === null))
                throw new \Exception('Campo Setor é obrigatório quando o nível de acesso é Produção.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_perfis
                    set
                        nome = :nome,
                        administrativo = :administrativo,
                        ativo = :ativo
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':administrativo', $request['administrativo']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $perfilId = $request['id'];
                $result = $stmt->execute() ? 'Success' : 'Failure';

                $msg = 'Perfil atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_perfis
                    set
                        nome = :nome,
                        administrativo = :administrativo,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':administrativo', $request['administrativo']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $result = $stmt->execute() ? 'Success' : 'Failure';
                $perfilId = $this->pdo->lastInsertId();

                $msg = 'Perfil cadastrado com sucesso.';
            }

            // Relação perfis x setores
            if($request['setores'] !== null and count($request['setores']) > 0){
                $sql = '
                    delete from pcp_perfis_setores
                    where id_perfil = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $perfilId);
                $stmt->execute();

                foreach($request['setores'] as $key => $setor){
                    $sql = '
                        insert into pcp_perfis_setores
                        set
                            id_perfil = :id_perfil,
                            id_setor = :id_setor
                    ';

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':id_perfil', $perfilId);
                    $stmt->bindParam(':id_setor', $setor['idSetor']);
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

    public function deletePerfil($filters){
        try{
            $sql = '
                delete from pcp_perfis
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Perfil removido com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }
}