<?php

class Funcionarios{
    public function __construct($db){
        $this->pdo = $db;
        //require_once 'goods.php';
    }

    public function getFuncionarios($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select * from pcp_funcionarios
            '.$where.'
            order by nome;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'nome' => $row->nome,
                'matricula' => $row->matricula,
                'salario' => $row->salario,
                'salarioBase' => $row->salario_base,
                'linha' => $row->linha,
                'setor' => $row->setor,
                'ativo' => $row->ativo
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateFuncionario($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('salario', $request) or $request['salario'] === '' or $request['salario'] === null)
                throw new \Exception('Campo Salário é obrigatório.');
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_funcionarios
                    set
                        nome = :nome,
                        matricula = :matricula,
                        salario = :salario,
                        salario_base = :salarioBase,
                        linha = :linha,
                        setor = :setor,
                        ativo = :ativo
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':id', $request['id']);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':matricula', $request['matricula']);
                $stmt->bindParam(':salario', $request['salario']);
                $stmt->bindParam(':salarioBase', $request['salarioBase']);
                $stmt->bindParam(':linha', $request['linha']);
                $stmt->bindParam(':setor', $request['setor']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Funcionário atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_funcionarios
                    set
                        nome = :nome,
                        matricula = :matricula,
                        salario = :salario,
                        salario_base = :salarioBase,
                        linha = :linha,
                        setor = :setor,
                        ativo = :ativo
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':nome', $request['nome']);
                $stmt->bindParam(':matricula', $request['matricula']);
                $stmt->bindParam(':salario', $request['salario']);
                $stmt->bindParam(':salarioBase', $request['salarioBase']);
                $stmt->bindParam(':linha', $request['linha']);
                $stmt->bindParam(':setor', $request['setor']);
                $stmt->bindParam(':ativo', $request['ativo']);
                $stmt->execute();

                $msg = 'Funcionário cadastrado com sucesso.';
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

    public function deleteFuncionario($filters){
        try{
            $sql = '
                delete from pcp_funcionarios
                where id = :id
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Funcionário removido com sucesso.'
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