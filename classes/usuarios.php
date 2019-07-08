<?php

class Usuarios{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function login($request){
        try {
            if(!array_key_exists('email', $request) or $request['email'] === '' or $request['email'] === null)
                throw new \Exception('Campo E-mail é obrigatório.');
            if(!array_key_exists('senha', $request) or $request['senha'] === '' or $request['senha'] === null)
                throw new \Exception('Campo Senha é obrigatório.');

            $senha = md5($request['senha']);
            $ativo = 'Y';
            
            $sql = '
                select u.id, u.nome, u.id_perfil, p.nome nome_perfil, p.administrativo, ps.id_setor, s.nome nome_setor, lps.ordem
                from pcp_usuarios u
                join pcp_perfis p on p.id = u.id_perfil
                left join pcp_perfis_setores ps on ps.id_perfil = u.id_perfil
                left join pcp_setores s on s.id = ps.id_setor
                left join pcp_linhas_de_producao_setores lps on lps.id_setor = ps.id_setor
                where
                    u.email = :email
                    and u.senha = :senha
                    and u.ativo = :ativo
                    AND (lps.ordem is not NULL OR s.nome IS NULL)
                group by ps.id_setor
                order by lps.ordem, ps.id_setor;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':email', $request['email']);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':ativo', $ativo);
            $stmt->execute();

            $setores = array();
            $prevId = 0;
            $i = 0;
            while ($row = $stmt->fetch()) {
                // Primeira linha
                if($i === 0){
                    $responseData = array(
                        'id' => (int)$row->id,
                        'nome' => $row->nome,
                        'perfil' => array(
                            'id' => $row->id_perfil,
                            'nome' => $row->nome_perfil
                        ),
                        'administrativo' => $row->administrativo
                    );
                    $i++;
                }
                // Demais linhas
                if($row->id_setor !== null){
                    $setores[] = array(
                        'id' => $row->id_setor,
                        'nome' => $row->nome_setor,
                        'ordem' => $row->ordem
                    );
                }
                $prevId = $row->id;
            }
            $responseData['setores'] = $setores;

            if($prevId > 0){
                header('token: '.session_id());
                return json_encode(array(
                    'success' => true,
                    'msg' => 'Usuário logado com sucesso.',
                    'payload' => $responseData
                ));
            } else{
                return json_encode(array(
                    'success' => false,
                    'msg' => 'Endereço de e-mail ou senha incorretos.'
                ));
            }

        }catch(\Exception $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function logout(){
        if(session_destroy()){
            return json_encode(array(
                'success' => true,
                'msg' => 'Sessão encerrada.'
            ));
        }
    }

    public function getUsuarios($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'u.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select u.id, u.nome, u.email, u.id_perfil, p.nome nome_perfil, u.ativo
            from pcp_usuarios u
            left join pcp_perfis p on p.id = u.id_perfil
            '.$where.'
            order by u.id;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'email' => $row->email,
                'perfil' => array(
                    'id' => (int)$row->id_perfil,
                    'nome' => $row->nome_perfil,
                ),
                'ativo' => $row->ativo
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateUsuario($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('email', $request) or $request['email'] === '' or $request['email'] === null)
                throw new \Exception('Campo E-mail é obrigatório.');
            if(!array_key_exists('senha', $request) or $request['senha'] === '' or $request['senha'] === null)
                throw new \Exception('Campo Senha é obrigatório.');
            if(!array_key_exists('idPerfil', $request) or $request['idPerfil'] === '' or $request['idPerfil'] === null)
                throw new \Exception('Campo Perfil é obrigatório.');

            

            $senha = md5($request['senha']);
            if($request['id']){
                // Edit
                $sql = '
                    select id from pcp_usuarios
                    where email = :email and id <> :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':email', $request['email']);
                $stmt->bindParam(':id', $request['id']);

                $stmt->execute();
                if(count($stmt->fetchAll()) > 0){
                    throw new \Exception('Endereço de Email informado já foi cadastrado.');
                }

                $sql = '
                    update pcp_usuarios
                    set
                        nome = :nome,
                        email = :email,
                        senha = :senha,
                        id_perfil = :idPerfil,
                        ativo = :ativo
                    where id = :id;
                ';


                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':email', $request['email']);
                $stmt->bindParam(':senha', $senha);
                $stmt->bindParam(':idPerfil', $request['idPerfil']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Usuário atualizado com sucesso.';
            }
            else{
                $sql = '
                    select id from pcp_usuarios
                    where email = :email;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':email', $request['email']);
                $stmt->execute();
                if(count($stmt->fetchAll()) > 0){
                    throw new \Exception('Endereço de Email informado já foi cadastrado.');
                }

                $sql = '
                    insert into pcp_usuarios
                    set
                        nome = :nome,
                        email = :email,
                        senha = :senha,
                        id_perfil = :idPerfil,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':email', $request['email']);
                $stmt->bindParam(':senha', $senha);
                $stmt->bindParam(':idPerfil', $request['idPerfil']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Usuário cadastrado com sucesso.';
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

    public function deleteUsuario($filters){
        try{
            $sql = '
                delete from pcp_usuarios
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Usuario removido com sucesso.'
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