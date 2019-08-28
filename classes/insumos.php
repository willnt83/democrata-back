<?php

class Insumos{
    public function __construct($db){
        $this->pdo = $db;
    }

    public function getInsumos($filters){
        $where = '';
        if(count($filters) > 0){
            $where = 'where ';
            $i = 0;
            foreach($filters as $key => $value){
                $and = $i > 0 ? ' and ' : '';
                $where .= $and.'insumos.'.$key.' = :'.$key;
                $i++;
            }
        }

        $sql = '
            select  insumos.id, insumos.nome, insumos.ins, insumos.ativo, insumos.categoria, 
                    unidadesMedida.id idUnidadeMedida, unidadesMedida.nome nomeUnidadeMedida, unidadesMedida.unidade unidadeUnidadeMedida,
                    unidade.id idUnidade, unidade.nome nomeUnidade
            from    pcp_insumos as insumos
                    join pcp_unidades_medida as unidadesMedida on insumos.id_unidade_medida = unidadesMedida.id
                    left join pcp_unidades as unidade on insumos.id_unidade = unidade.id
            '.$where.'
            order by id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($filters);

        $responseData = array();
        while ($row = $stmt->fetch()) {
            $row->id = (int) $row->id;
            $row->idUnidade = $row->idUnidade ? (int) $row->idUnidade : null;
            $row->idUnidadeMedida = (int) $row->idUnidadeMedida;
            $responseData[] = $row;
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function createUpdateInsumo($request){
        try{
            // Validações
            if(!array_key_exists('nome', $request) or $request['nome'] === '' or $request['nome'] === null)
                throw new \Exception('Campo Nome é obrigatório.');
            if(!array_key_exists('ins', $request) or $request['ins'] === '' or $request['ins'] === null)
                throw new \Exception('Campo INS é obrigatório.');                
            if(!array_key_exists('ativo', $request) or $request['ativo'] === '' or $request['ativo'] === null)
                throw new \Exception('Campo Ativo é obrigatório.');
            if(!array_key_exists('categoria', $request) or $request['categoria'] === '' or $request['categoria'] === null)
                throw new \Exception('Campo Categoria é obrigatório.');
            if(!array_key_exists('unidademedida', $request) or $request['unidademedida'] === '' or $request['unidademedida'] === null)
                throw new \Exception('Campo Unidade de Medida é obrigatório.');    

            if($request['id']){
                // Edit
                $sql = '
                    update pcp_insumos
                    set
                        nome = :nome,
                        ins = :ins,
                        ativo = :ativo,
                        categoria =:categoria,
                        id_unidade_medida = :unidademedida,
                        id_unidade = :unidade
                    where id = :id';
                $msg = 'Insumo atualizado com sucesso.';
            }
            else{
                $sql = '
                    insert into pcp_insumos
                    set
                        nome = :nome,
                        ins = :ins,
                        ativo = :ativo,
                        categoria = :categoria,
                        id_unidade_medida = :unidademedida,
                        id_unidade = :unidade';
                $msg = 'Insumo cadastrado com sucesso.';
            }

            // Executing the statement
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':nome', $request['nome']);
            $stmt->bindParam(':ins', $request['ins']);
            $stmt->bindParam(':ativo', $request['ativo']);
            $stmt->bindParam(':categoria', $request['categoria']);   
            $stmt->bindParam(':unidademedida', $request['unidademedida']);

            // Campos não obrigatórios
            if(!array_key_exists('unidade', $request) or $request['unidade'] === '' or $request['unidade'] === null)
                $stmt->bindParam(':unidade', $n = null, PDO::PARAM_INT);
            else
                $stmt->bindParam(':unidade', $request['unidade']);

            if($request['id']) $stmt->bindParam(':id', $request['id']);
            $stmt->execute();

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

    public function deleteInsumo($filters){
        try{
            $sql = 'delete from pcp_insumos where id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $filters['id']); 
            $stmt->execute();

            return json_encode(array(
                'success' => true,
                'msg' => 'Insumo removido com sucesso.'
            ));
        }
        catch(PDOException $e){
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function importInsumos($directory = '', $uploadedFiles = null){
        if($directory and $uploadedFiles){
            require_once '../shared/UploadFile.php';
        
            $uploadedFile = $uploadedFiles['file'];
            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $filename = UploadFile::moveUploadedFile($directory, $uploadedFile);
                if($filename){
                    $linecont = 1;
                    $file = fopen($directory . DIRECTORY_SEPARATOR . $filename,"r");
                    while(!feof($file)){
                        $fileArray = fgetcsv($file, 1000, '\t');
                        if($fileArray and count($fileArray) > 0){
                            foreach($fileArray as $key=>$value){
                                try{
                                    $content = explode(';', $value);
                                    if($content and count($content) >= 4){
                                        // Verifica se já existe o insumo
                                        if($content[0] !== '' and $content[0] and $content[1] !== '' and $content[1]){
                                            $sqlInsumo = '  select  count(*) as total 
                                                            from    pcp_insumos p 
                                                            where   LOWER(p.ins) COLLATE latin1_general_ci = LOWER(:ins) COLLATE utf8_general_ci and 
                                                                    LOWER(p.nome) COLLATE latin1_general_ci = LOWER(:nome) COLLATE utf8_general_ci 
                                                            limit   1';
                                            $stmtInsumo = $this->pdo->prepare($sqlInsumo);
                                            $stmtInsumo->bindParam(':ins', str_replace('INS-','',trim($content[0])));
                                            $stmtInsumo->bindParam(':nome', trim($content[1]));
                                            $stmtInsumo->execute();
                                            $rowUnidade = $stmtInsumo->fetch();
                                            $insertInsumo = ($rowUnidade->total and $rowUnidade->total > 0) ? false : true;
                                        } else {
                                            $insertInsumo = true;
                                        }

                                        if($insertInsumo){
                                            // Retorna a unidade de medida
                                            if($content[3] and $content[3] !== null){
                                                $sqlUnidade = 'select id from pcp_unidades_medida where LOWER(unidade) = :unidade order by id desc limit 1';
                                                $stmtUnidade = $this->pdo->prepare($sqlUnidade);
                                                $stmtUnidade->bindParam(':unidade', strtolower($content[3]));
                                                $stmtUnidade->execute();
                                                $rowUnidade = $stmtUnidade->fetch();
                                                $content[3] = ($rowUnidade->id) ? (int) $rowUnidade->id : null;
                                            } else {
                                                $content[3] = null;
                                            }
                                            if($content[3] && $content[3] > 0){
                                                $stmt = null;

                                                $sql = '
                                                    insert into pcp_insumos
                                                    set
                                                        nome = :nome,
                                                        ins = :ins,
                                                        ativo = "Y",
                                                        categoria = :categoria,
                                                        id_unidade_medida = :unidademedida,
                                                        comprimento = :comprimento,
                                                        largura = :largura,
                                                        altura = :altura';
                                                $stmt = $this->pdo->prepare($sql);

                                                if($content[0] === '' or $content[0] === null)
                                                    $stmt->bindParam(':ins', $n = null);
                                                else
                                                    $stmt->bindParam(':ins', str_replace('INS-','',$content[0])); 

                                                if($content[1] === '' or $content[1] === null)
                                                    $stmt->bindParam(':nome', $n = null);
                                                else
                                                    $stmt->bindParam(':nome', trim($content[1]));

                                                if($content[2] === '' or $content[2] === null)
                                                    $stmt->bindParam(':categoria', $n = null);
                                                else
                                                    $stmt->bindParam(':categoria', trim($content[1]));

                                                if($content[3] === '' or $content[3] === null or !is_numeric($content[3]))
                                                    $stmt->bindParam(':unidademedida', $n = null, PDO::PARAM_INT);
                                                else
                                                    $stmt->bindParam(':unidademedida', $content[3]);

                                                if(!array_key_exists(4, $content) or !isset($content[4]) or $content[4] === '' or $content[4] === null or !is_numeric($content[4]))
                                                    $stmt->bindParam(':comprimento', $n = null);
                                                else
                                                    $stmt->bindParam(':comprimento', number_format($content[4],2,'.',''));

                                                if(!array_key_exists(5, $content) or !isset($content[5]) or $content[5] === '' or $content[5] === null or !is_numeric($content[5]))
                                                    $stmt->bindParam(':largura', $n = null);
                                                else
                                                    $stmt->bindParam(':largura', number_format($content[5],2,'.',''));

                                                if(!array_key_exists(6, $content) or !isset($content[6]) or $content[6] === '' or $content[6] === null or !is_numeric($content[6]))
                                                    $stmt->bindParam(':altura', $n = null);
                                                else
                                                    $stmt->bindParam(':altura', number_format($content[6],2,'.',''));                                            

                                                $stmt->execute();
                                            }
                                        }
                                    }
                                } catch(\Exception $e) {
                                    // Erro
                                }
                            }
                        }
                        $linecont++;
                    }
                    fclose($file);

                    return json_encode(array(
                        'success' => true,
                        'msg' => 'Insumos importados com sucesso!'
                    ));                    
                }
            }
        }

        return json_encode(array(
            'success' => false,
            'msg' => 'Não há arquivos válido para importar! Tente novamente.'
        ));
    }
}