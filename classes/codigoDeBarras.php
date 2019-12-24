<?php
class CodigoDeBarras{
    public function __construct($db){
        $this->pdo = $db;
        $this->debug = false;
    }

    public function date2sql($date){
		$arrayDate = explode('/', $date);
		return $arrayDate[2].'-'.$arrayDate[1].'-'.$arrayDate[0];
    }

    public function sql2date($date){
		$arrayDate = explode('-', $date);
		return $arrayDate[2].'/'.$arrayDate[1].'/'.$arrayDate[0];
    }

    public function generateImage($img, $file){
        $file_parts = explode('/', $file);
        if(!is_dir($file_parts[0].'/'.$file_parts[1])){
            mkdir($file_parts[0].'/'.$file_parts[1], 0777, true);
        }

        $image_parts = explode(";base64,", $img);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        
        file_put_contents($file, $image_base64);
        return $file;
    }

    public function gerarCodigosDeBarrasCSV($filters){
        // Verificação de existencia de registros de código de barras da produção informada
        $sqlVer = '
            select count(*) as registros
                from pcp_codigo_de_barras
                where
                    id_producao = :id_producao;
            ';
        $stmtVer = $this->pdo->prepare($sqlVer);
        $stmtVer->bindParam(':id_producao', $filters['id_producao']);
        $stmtVer->execute();
        $rowVer = $stmtVer->fetch();
        $existeRegistro = $rowVer->registros > 0 ? true : false;

        if(!file_exists('barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.csv')){
            // Cria o diretório se não existir
            if (!file_exists('barcodes/'.$filters['id_producao'])) {
                mkdir('barcodes/'.$filters['id_producao'], 0777, true);
            }

            $where = '';
            if(count($filters) > 0){
                $where = 'where ';
                $i = 0;
                foreach($filters as $key => $value){
                    $and = $i > 0 ? ' and ' : '';
                    $where .= $and.'pa.'.$key.' = :'.$key;
                    $i++;
                }
            }

            $sql = '
                SELECT
                    pa.id_producao,
                    pa.id_produto,
                    p.nome nome_produto,
                    cor.nome cor_produto,
                    p.sku sku_produto,
                    p.codigo codigo_produto,
                    plsc.id_conjunto,
                    pa.id_setor,
                    s.nome nome_setor,
                    pa.id_subproduto,
                    ss.nome nome_subproduto,
                    pa.total_quantidade,
                    pa.data_inicial,
                    lps.ordem,
                    cs.pontos_subproduto as pontos
                from pcp_producoes_acompanhamento pa
                join pcp_setores s on s.id = pa.id_setor
                join pcp_producoes pro on pro.id = pa.id_producao
                join pcp_produtos p on p.id = pa.id_produto
                join pcp_linhas_de_producao_setores lps on lps.id_setor = pa.id_setor and lps.id_linha_de_producao = p.id_linha_de_producao
                join pcp_subprodutos ss on ss.id = pa.id_subproduto
                join pcp_cores cor on cor.id = p.id_cor
                JOIN pcp_produtos_linhas_setores_conjunto plsc ON plsc.id_produto = pa.id_produto AND plsc.id_linha_de_producao = p.id_linha_de_producao AND plsc.id_setor = pa.id_setor
                JOIN pcp_conjuntos_subprodutos cs ON cs.id_conjunto = plsc.id_conjunto AND cs.id_subproduto = pa.id_subproduto
                '.$where.'
                order by lps.ordem, pa.id_setor, ss.nome, pa.id
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filters);
            $barCodes = array();

            // Monta o vetor com dois elementos: strBase64 (código de barras) e file (caminho completo do arquivo de imagem a ser gerado)
            // Insere o código de barras gerado em pcp_codigo_de_barras
            $currDate = date('Y-m-d');
            //$first = true;
            $insertData = array();
            $output = array();
            $lastSetor = null;
            while($row = $stmt->fetch()){
                // Nome do setor
                if($row->id_setor != $lastSetor){
                    $output[] = array('', '', utf8_decode($row->nome_setor));
                    $lastSetor = $row->id_setor;
                }

                $pontos = $row->pontos == null ? 0 : $row->pontos;

                if($row->total_quantidade > 1){
                    $i = 1;
                    while($i <= $row->total_quantidade){
                        $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-".$i;
                        //$output[] = array(utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                        $output[] = array(utf8_decode($row->sku_produto), utf8_decode($row->codigo_produto), utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                        $insertData[] = '('.$row->id_producao.', '.$row->id_produto.', '.$row->id_conjunto.', '.$row->id_setor.', '.$row->id_subproduto.', '.$i.', "'.$barCode.'", "'.$currDate.'", '.$pontos.')';
                        $i++;
                    }
                }
                
                else{
                    $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-1";
                    //$output[] = array(utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                    $output[] = array(utf8_decode($row->sku_produto), utf8_decode($row->codigo_produto), utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                    $insertData[] = '('.$row->id_producao.', '.$row->id_produto.', '.$row->id_conjunto.', '.$row->id_setor.', '.$row->id_subproduto.', 1, "'.$barCode.'", "'.$currDate.'", '.$pontos.')';
                }
            }

            // Não há registros em pcp_codigo_de_barras
            if(!$existeRegistro){
                // Inserção no banco
                $valuesStr = '';
                $comma = '';
                $first = true;

                foreach($insertData as $row){
                    if($first){
                        $comma = '';
                        $first = false;
                    }
                    else
                        $comma = ', ';

                    $valuesStr .= $comma.$row;
                }
                
                $sql2 = '
                    insert into pcp_codigo_de_barras
                    (id_producao, id_produto, id_conjunto, id_setor, id_subproduto, sequencial, codigo, dt_geracao, pontos)
                    values
                    '.$valuesStr.';
                ';
                
                $stmt2 = $this->pdo->prepare($sql2);
                $stmt2->execute();
            }

            // Geração do CSV
            /*
            echo "\nOUTPUT\n";
            print_r($output);
            */
            $fp = fopen('barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.csv', 'w');

            foreach($output as $fields) {
                fputcsv($fp, $fields, ';');
            }

            fclose($fp);

            return json_encode(array(
                'success' => true,
                'msg' => 'Relatório gerado com sucesso.',
                'payload' => array(
                    'url' => 'barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.csv'
                )
            ));
        }
        else
            $path = 'barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.csv';

        return json_encode(array(
            'success' => true,
            'payload' => array(
                'url' => $path
            )
        ));
    }

    public function gerarCodigosDeBarras($filters){
        // Verificação de existencia de registros de código de barras da produção informada
        $sqlVer = '
            select count(*) as registros
                from pcp_codigo_de_barras
                where
                    id_producao = :id_producao;
            ';
        $stmtVer = $this->pdo->prepare($sqlVer);
        $stmtVer->bindParam(':id_producao', $filters['id_producao']);
        $stmtVer->execute();
        $rowVer = $stmtVer->fetch();
        $existeRegistro = $rowVer->registros > 0 ? true : false;


        if(!file_exists('barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.pdf')){
            //http://www.fpdf.org/
            require('../vendor/fpdf/fpdf.php');
            //https://github.com/picqer/php-barcode-generator
            $generator = new Picqer\Barcode\BarcodeGeneratorPNG();

            $where = '';
            if(count($filters) > 0){
                $where = 'where ';
                $i = 0;
                foreach($filters as $key => $value){
                    $and = $i > 0 ? ' and ' : '';
                    $where .= $and.'pa.'.$key.' = :'.$key;
                    $i++;
                }
            }

            $sql = '
                SELECT
                    pa.id_producao,
                    pa.id_produto,
                    p.nome nome_produto,
                    cor.nome cor_produto,
                    plsc.id_conjunto,
                    pa.id_setor,
                    s.nome nome_setor,
                    pa.id_subproduto,
                    ss.nome nome_subproduto,
                    pa.total_quantidade,
                    pa.data_inicial,
                    lps.ordem,
                    cs.pontos_subproduto as pontos
                from pcp_producoes_acompanhamento pa
                join pcp_setores s on s.id = pa.id_setor
                join pcp_producoes pro on pro.id = pa.id_producao
                join pcp_produtos p on p.id = pa.id_produto
                join pcp_linhas_de_producao_setores lps on lps.id_setor = pa.id_setor and lps.id_linha_de_producao = p.id_linha_de_producao
                join pcp_subprodutos ss on ss.id = pa.id_subproduto
                join pcp_cores cor on cor.id = p.id_cor
                JOIN pcp_produtos_linhas_setores_conjunto plsc ON plsc.id_produto = pa.id_produto AND plsc.id_linha_de_producao = p.id_linha_de_producao AND plsc.id_setor = pa.id_setor
                JOIN pcp_conjuntos_subprodutos cs ON cs.id_conjunto = plsc.id_conjunto AND cs.id_subproduto = pa.id_subproduto
                '.$where.'
                order by lps.ordem, pa.id_setor, ss.nome, pa.id
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filters);
            $barCodes = array();

            // Monta o vetor com dois elementos: strBase64 (código de barras) e file (caminho completo do arquivo de imagem a ser gerado)
            // Insere o código de barras gerado em pcp_codigo_de_barras
            $currDate = date('Y-m-d');
            //$first = true;
            $insertData = array();
            while($row = $stmt->fetch()){
                 $pontos = $row->pontos == null ? 0 : $row->pontos;

                if($row->total_quantidade > 1){
                    $i = 1;
                    while($i <= $row->total_quantidade){
                        $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-".$i;
                        $barCodes[] = array(
                            'setor' => $row->nome_setor,
                            'dataInicial' => $row->data_inicial,
                            'nomeProduto' => $row->nome_produto,
                            'corProduto' => $row->cor_produto,
                            'nomeSubproduto' => $row->nome_subproduto,
                            'strBase64' => $barCode,
                            'file' => 'barcodes/'.$row->id_producao.'/'.$barCode.'.png'
                        );

                        $insertData[] = '('.$row->id_producao.', '.$row->id_produto.', '.$row->id_conjunto.', '.$row->id_setor.', '.$row->id_subproduto.', '.$i.', "'.$barCode.'", "'.$currDate.'", '.$pontos.')';
                        $i++;
                    }
                }
                else{
                    $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-1";
                    $barCodes[] = array(
                        'setor' => $row->nome_setor,
                        'dataInicial' => $row->data_inicial,
                        'nomeProduto' => $row->nome_produto,
                        'corProduto' => $row->cor_produto,
                        'nomeSubproduto' => $row->nome_subproduto,
                        'strBase64' => $barCode,
                        'file' => 'barcodes/'.$row->id_producao.'/'.$barCode.'.png'
                    );

                    $insertData[] = '('.$row->id_producao.', '.$row->id_produto.', '.$row->id_conjunto.', '.$row->id_setor.', '.$row->id_subproduto.', 1, "'.$barCode.'", "'.$currDate.'", '.$pontos.')';
                }
            }

            // Inserção no banco
            if(!$existeRegistro){
                $valuesStr = '';
                $comma = '';
                $first = true;

                foreach($insertData as $row){
                    if($first){
                        $comma = '';
                        $first = false;
                    }
                    else
                        $comma = ', ';

                    $valuesStr .= $comma.$row;
                }

                $sql2 = '
                    insert into pcp_codigo_de_barras
                        (id_producao, id_produto, id_conjunto, id_setor, id_subproduto, sequencial, codigo, dt_geracao, pontos)
                        values
                        '.$valuesStr.';
                ';
                $stmt2 = $this->pdo->prepare($sql2);
                $stmt2->execute();
            }

            // Geração do PDF
            $i = 0;
            $pdf = new FPDF();
            $lastSetor = null;

            while($i < count($barCodes)){
                if($this->debug)echo "\n";
                if($this->debug)echo "\ni: ".$i;
                if($i === 0) $pdf->AddPage();

                $pdf->SetFont('Arial', '', 5);
                $sectorChange = array();
                $sumSector = 0;
                $aux = 0;

                while($aux < 4){
                    if(($i+$aux) < count($barCodes) and $barCodes[$i+$aux]['setor'] != $lastSetor){
                        $lastSetor = $barCodes[$i+$aux]['setor'];
                        $sectorChange[] = $aux;
                        //break;
                    }
                    $aux++;
                }
                if($this->debug)echo "\nsectorChange\n";
                if($this->debug)print_r($sectorChange);

                $c = 0;
                $aux = 0;

                $lastChanged = false;
                while($aux < 4){
                    if(in_array($c, $sectorChange) && $lastChanged == false){
                        if($this->debug)echo "\nsectorChange[".$aux."]: (vazio)";
                        $pdf->Cell(47, 2, '', 0, 0, 'C');
                        $lastChanged = true;
                        $sumSector++;
                    }
                    else if(($i+$c) < count($barCodes)){
                        $lastChanged = false;
                        if($this->debug)echo "\nimpressao normal: ".utf8_decode($barCodes[$i+$c]['nomeProduto']).'('.utf8_decode($barCodes[$i+$c]['corProduto']).')-'.utf8_decode($barCodes[$i+$c]['nomeSubproduto']);
                        $pdf->Cell(47, 2, utf8_decode($barCodes[$i+$c]['nomeProduto']).'('.utf8_decode($barCodes[$i+$c]['corProduto']).')-'.utf8_decode($barCodes[$i+$c]['nomeSubproduto']), 0, 0, 'C');
                        $c++;
                    }
                    $aux++;
                }

                $pdf->Ln();
                $pdf->SetFont('Arial','', 7);

                $c = 0;
                $aux = 0;
                $lastChanged = false;
                while($aux < 4){
                    if(in_array($c, $sectorChange) && $lastChanged == false){
                        if($this->debug) echo "\nsectorChange[".$aux."]: ".utf8_decode($barCodes[$i+$c]['setor']);
                        $pdf->Cell(47, 10, utf8_decode($barCodes[$i+$c]['setor']), 0, 0, 'C', false);
                        $lastChanged = true;
                    }
                    else if(($i+$c) < count($barCodes)){
                        $lastChanged = false;
                        if($this->debug)echo "\nimpressao normal: (codigo de barras)";
                        // Gerando a string base64 da imagem
                        $strBase64 = 'data:image/png;base64,'.base64_encode($generator->getBarcode($barCodes[$i+$c]['strBase64'], $generator::TYPE_CODE_128));
                        // Gerando o arquivo de imagem
                        $img = $this->generateImage($strBase64, $barCodes[$i+$c]['file']);
                        $pdf->Cell(47, 10, $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 0, 0, 'C', false);
                        $c++;
                    }
                    $aux++;
                }

                $pdf->Ln();

                $c = 0;
                $aux = 0;
                if($this->debug)echo "\n--------while4";
                $lastChanged = false;
                while($aux < 4){
                    if(in_array($c, $sectorChange) && $lastChanged == false){
                        if($this->debug)echo "\nsectorChange[".$c."]: (vazio)";
                        $pdf->Cell(47, 3.7, '', 0, 0, 'C', false);
                        $lastChanged = true;
                    }
                    else if(($i+$c) < count($barCodes)){
                        $lastChanged = false;
                        if($this->debug)echo "\nimpressao normal: ".$barCodes[$i+$c]['strBase64'];
                        $pdf->Cell(47, 3.7, $barCodes[$i+$c]['strBase64'], 0, 0, 'C', false);
                        $c++;
                    }
                    $aux++;
                }


                $pdf->Ln();
                $pdf->Cell(188, 2, '', 0, 0, 'C', false);
                $pdf->Ln();
                $i += (4 - $sumSector);
            }
            
            
            $path = 'barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.pdf';
            $pdf->Output('F', $path, true);
        }
        else
            $path = 'barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.pdf';

        return json_encode(array(
            'success' => true,
            'payload' => array(
                'url' => $path
            )
        ));
    }

    public function lancamentoCodigoDeBarras($request){
        try{
            // Validações
            // Verifica se idFuncionario foi informado
            if(!array_key_exists('idFuncionario', $request)
                or $request['idFuncionario'] === ''
                or $request['idFuncionario'] === null)
                throw new \Exception('Funcionário não informado');

            $sql = '
                select id, lancado, id_setor
                from pcp_codigo_de_barras cb
                where cb.codigo = :codigo;
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['barcode']);
            $stmt->execute();
            $row = $stmt->fetch();

            if($row){
                $todayDT = new Datetime();
                $today = $todayDT->format('Y-m-d');
                if($row->lancado == 'N'){
                    $sql = '
                        update pcp_codigo_de_barras
                        set
                            id_funcionario = :idFuncionario,
                            lancado = "Y",
                            conferido = "N",
                            estornado = "N",
                            dt_lancamento = :dataLancamento
                        where id = :id;
                    ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':idFuncionario', $request['idFuncionario']);
                    $stmt->bindParam(':dataLancamento', $today);
                    $stmt->bindParam(':id', $row->id);
                    $stmt->execute();

                    $msg = 'Código de barras registrado com sucesso';
                }
                else{
                    throw new \Exception('Código de barras já lançado');
                }
            }
            else{
                throw new \Exception('Código de barras inexistente');
            }

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

    public function getCodigosDeBarrasLancados($request){
        $dataInicial = $this->date2sql($request['dataInicial']);
        $dataFinal = $this->date2sql($request['dataFinal']);

        $sql = '
            SELECT
                cb.id,
                cb.id_producao, pro.nome nome_producao,
                cb.id_produto, p.nome nome_produto, cor.nome cor_produto,
                cb.id_conjunto, c.nome nome_conjunto,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.sequencial, cb.codigo, cb.lancado, cb.conferido
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes pro ON pro.id = cb.id_producao
            JOIN pcp_produtos p ON p.id = cb.id_produto
            JOIN pcp_cores cor on cor.id = p.id_cor
            JOIN pcp_conjuntos c ON c.id = cb.id_conjunto
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            where
                cb.id_funcionario = :idFuncionario
                and cb.dt_lancamento >= :dt_lancamento_inicial
                and cb.dt_lancamento <= :dt_lancamento_final
                and cb.lancado = "Y"
            ORDER BY cb.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idFuncionario', $request['idFuncionario']);
        $stmt->bindParam(':dt_lancamento_inicial', $dataInicial);
        $stmt->bindParam(':dt_lancamento_final', $dataFinal);
        $stmt->execute();
        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'producao' => array(
                    'id' => $row->id_producao,
                    'nome' => $row->nome_producao
                ),
                'produto' => array(
                    'id' => $row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto
                ),
                'conjunto' => array(
                    'id' => $row->id_conjunto,
                    'nome' => $row->nome_conjunto
                ),
                'setor' => array(
                    'id' => $row->id_setor,
                    'nome' => $row->nome_setor
                ),
                'subproduto' => array(
                    'id' => $row->id_subproduto,
                    'nome' => $row->nome_subproduto,
                    'sequencial' => $row->sequencial
                ),
                'codigoDeBarras' => array(
                    'codigo' => $row->codigo,
                    'lancado' => $row->lancado,
                    'conferido' => $row->conferido
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'msg' => 'Lista de códigos de barras lançados recuperada com sucesso.',
            'payload' => $responseData
        ));
    }

    public function getCodigosDeBarrasProducao($request){
        $sql = '
            SELECT
                cb.id,
                cb.id_producao, pro.nome nome_producao,
                cb.id_produto, p.nome nome_produto, cor.nome cor_produto,
                cb.id_conjunto, c.nome nome_conjunto,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.sequencial, cb.codigo, cb.lancado, cb.conferido
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes pro ON pro.id = cb.id_producao
            JOIN pcp_produtos p ON p.id = cb.id_produto
            JOIN pcp_cores cor on cor.id = p.id_cor
            JOIN pcp_conjuntos c ON c.id = cb.id_conjunto
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            WHERE
                cb.id_producao = :idProducao
                and cb.id_setor = :idSetor
                and cb.id_subproduto = :idSubproduto
            ORDER BY cb.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idProducao', $request['idProducao']);
        $stmt->bindParam(':idSetor', $request['idSetor']);
        $stmt->bindParam(':idSubproduto', $request['idSubproduto']);
        $stmt->execute();
        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'producao' => array(
                    'id' => $row->id_producao,
                    'nome' => $row->nome_producao
                ),
                'produto' => array(
                    'id' => $row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto
                ),
                'conjunto' => array(
                    'id' => $row->id_conjunto,
                    'nome' => $row->nome_conjunto
                ),
                'setor' => array(
                    'id' => $row->id_setor,
                    'nome' => $row->nome_setor
                ),
                'subproduto' => array(
                    'id' => $row->id_subproduto,
                    'nome' => $row->nome_subproduto,
                    'sequencial' => $row->sequencial
                ),
                'codigoDeBarras' => array(
                    'codigo' => $row->codigo,
                    'lancado' => $row->lancado,
                    'conferido' => $row->conferido
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'payload' => $responseData
        ));
    }

    public function conferenciaCodigoDeBarras($request){
        $sql = '
            select id, lancado, conferido, id_producao, id_produto, id_setor, id_subproduto
            from pcp_codigo_de_barras cb
            where
                cb.codigo = :codigo
                and cb.id_producao = :idProducao
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':codigo', $request['barcode']);
        $stmt->bindParam(':idProducao', $request['idProducao']);
        $stmt->execute();
        $row = $stmt->fetch();
        $payload = null;
        if($row){
            if($row->lancado == 'N'){
                $success = false;
                $msg = 'Código de barras não lançado.';
            }
            else{
                if($row->conferido == 'N'){
                    $todayDT = new Datetime();
                    $today = $todayDT->format('Y-m-d');
                    $sql = '
                        update pcp_codigo_de_barras
                        set
                            conferido = "Y",
                            estornado = "N",
                            dt_conferencia = :dataConferencia
                        where id = :id;
                    ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':dataConferencia', $today);
                    $stmt->bindParam(':id', $row->id);
                    $stmt->execute();

                    $sql = '
                        update pcp_producoes_acompanhamento pa
                        set
                            pa.realizado_quantidade = (pa.realizado_quantidade + 1)
                        where
                            pa.id_producao = :idProducao
                            and pa.id_produto = :idProduto
                            and pa.id_setor = :idSetor
                            and pa.id_subproduto = :idSubproduto;
                    ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':idProducao', $row->id_producao);
                    $stmt->bindParam(':idProduto', $row->id_produto);
                    $stmt->bindParam(':idSetor', $row->id_setor);
                    $stmt->bindParam(':idSubproduto', $row->id_subproduto);
                    $stmt->execute();
    
                    $success = true;
                    $msg = 'Código de barras conferido com sucesso.';
                    $payload = array('id' => $row->id);
                }
                else{
                    $success = false;
                    $msg = 'Código de barras já conferido.';
                }
            }
        }
        else{
            $success = false;
            $msg = 'Código de barras inexistente.';
        }

        return json_encode(array(
            'success' => $success,
            'msg' => $msg,
            'payload' => $payload
        ));
    }

    public function estornoCodigoDeBarras($request){
        $sql = '
            select id, estornado, conferido, id_producao, id_produto, id_setor, id_subproduto
            from pcp_codigo_de_barras cb
            where
                cb.codigo = :codigo
                and cb.id_producao = :idProducao;
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':codigo', $request['barcode']);
        $stmt->bindParam(':idProducao', $request['idProducao']);
        $stmt->execute();
        $row = $stmt->fetch();
        $payload = null;
        if($row){
            if($row->conferido == 'N'){
                $success = false;
                $msg = 'Código de barras não conferido.';
            }
            else{
                if($row->estornado == 'N'){
                    $todayDT = new Datetime();
                    $today = $todayDT->format('Y-m-d');
                    $sql = '
                        update pcp_codigo_de_barras
                        set
                            estornado = "Y",
                            conferido = "N",
                            lancado = "N",
                            dt_estorno = :dataEstorno
                        where id = :id;
                    ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':dataEstorno', $today);
                    $stmt->bindParam(':id', $row->id);
                    $stmt->execute();

                    $sql = '
                        update pcp_producoes_acompanhamento pa
                        set
                            pa.realizado_quantidade = (pa.realizado_quantidade - 1)
                        where
                            pa.id_producao = :idProducao
                            and pa.id_produto = :idProduto
                            and pa.id_setor = :idSetor
                            and pa.id_subproduto = :idSubproduto;
                    ';
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->bindParam(':idProducao', $row->id_producao);
                    $stmt->bindParam(':idProduto', $row->id_produto);
                    $stmt->bindParam(':idSetor', $row->id_setor);
                    $stmt->bindParam(':idSubproduto', $row->id_subproduto);
                    $stmt->execute();
    
                    $success = true;
                    $msg = 'Código de barras estornado com sucesso.';
                    $payload = array('id' => $row->id);
                }
                else{
                    $success = false;
                    $msg = 'Código de barras já estornado.';
                }
            }
        }
        else{
            $success = false;
            $msg = 'Código de barras inexistente.';
        }

        return json_encode(array(
            'success' => $success,
            'msg' => $msg,
            'payload' => $payload
        ));
    }

    public function getCodigoDeBarra($request){
        try{
            // Validações
            if(!array_key_exists('barcode', $request) or $request['barcode'] === '' or $request['barcode'] === null)
                throw new \Exception('Código de Barras não capturado corretamente! Tente novamente.');

            // Parâmetros
            $sqlWhere = '';
            if(isset($request['idProducao']) and $request['idProducao'] !== '')
                $sqlWhere .= ' and cb.id_producao = :idProducao';
            if(array_key_exists('idSetor', $request) and $request['idSetor'] !== '')
                $sqlWhere .= ' and cb.id_setor = :idSetor';
            if(array_key_exists('idSubproduto', $request) and $request['idSubproduto'] !== '')
                $sqlWhere .= ' and cb.id_subproduto = :idSubproduto';

            $sql = '
                SELECT
                    cb.id,
                    cb.id_producao, pro.nome nome_producao,
                    cb.id_produto, p.nome nome_produto, cor.nome cor_produto,
                    cb.id_conjunto, c.nome nome_conjunto,
                    cb.id_setor, s.nome nome_setor,
                    cb.id_subproduto, ss.nome nome_subproduto,
                    cb.id_funcionario, f.nome nome_funcionario,
                    cb.sequencial, cb.codigo, cb.lancado, cb.conferido,
                    cb.estornado, cb.defeito, 
                    ifnull(cb.qtdeDefeito, 0) qtdeDefeito
                FROM pcp_codigo_de_barras cb
                JOIN pcp_producoes pro ON pro.id = cb.id_producao
                JOIN pcp_produtos p ON p.id = cb.id_produto
                JOIN pcp_cores cor on cor.id = p.id_cor
                JOIN pcp_conjuntos c ON c.id = cb.id_conjunto
                JOIN pcp_setores s ON s.id = cb.id_setor
                JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
                LEFT JOIN pcp_funcionarios f on f.id = cb.id_funcionario
                where
                    cb.codigo = :codigo
                    '.$sqlWhere.'
                limit 1;
            ';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':codigo', $request['barcode']);

            // Bindins Parameters
            if(array_key_exists('idProducao', $request) and $request['idProducao'] !== '')
                $stmt->bindParam(':idProducao', $request['idProducao']);
            if(array_key_exists('idSetor', $request) and $request['idSetor'] !== '')
                $stmt->bindParam(':idSetor', $request['idSetor']);
            if(array_key_exists('idSubproduto', $request) and $request['idSubproduto'] !== '')
                $stmt->bindParam(':idSubproduto', $request['idSubproduto']);

            // Executing the query
            $stmt->execute();
            $responseData = array();
            while($row = $stmt->fetch()){
                $responseData = array(
                    'id' => (int) $row->id,
                    'producao' => array(
                        'id' => $row->id_producao,
                        'nome' => $row->nome_producao
                    ),
                    'produto' => array(
                        'id' => $row->id_produto,
                        'nome' => $row->nome_produto,
                        'cor' => $row->cor_produto
                    ),
                    'conjunto' => array(
                        'id' => $row->id_conjunto,
                        'nome' => $row->nome_conjunto
                    ),
                    'setor' => array(
                        'id' => $row->id_setor,
                        'nome' => $row->nome_setor
                    ),
                    'subproduto' => array(
                        'id' => $row->id_subproduto,
                        'nome' => $row->nome_subproduto,
                        'sequencial' => $row->sequencial
                    ),
                    'funcionario' => array(
                        'id' => $row->id_funcionario,
                        'nome' => $row->nome_funcionario
                    ),
                    'codigoDeBarras' => array(
                        'codigo' => $row->codigo,
                        'lancado' => $row->lancado,
                        'conferido' => $row->conferido,
                        'estornado' => $row->estornado,
                        'defeito' => $row->defeito,
                        'qtdeDefeito' => $row->qtdeDefeito,
                    )
                );
            }

            return json_encode(array(
                'success' => true,
                'msg' => 'Código de barras retornado com sucesso.',
                'payload' => count($responseData) > 0 ? $responseData : null
            ));
        }catch(\Exception $e){
            return json_encode(array(
                'success'   => false,
                'msg'       => $e->getMessage(),
                'payload'   => null
            ));
        }
    }

    public function getCodigosDeBarrasEstornados($request){
        $sql = '
            SELECT
                cb.id,
                cb.id_producao, pro.nome nome_producao,
                cb.id_produto, p.nome nome_produto, cor.nome cor_produto,
                cb.id_conjunto, c.nome nome_conjunto,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.id_funcionario, f.nome nome_funcionario,
                cb.sequencial, cb.codigo, cb.lancado, cb.conferido
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes pro ON pro.id = cb.id_producao
            JOIN pcp_produtos p ON p.id = cb.id_produto
            JOIN pcp_cores cor on cor.id = p.id_cor
            JOIN pcp_conjuntos c ON c.id = cb.id_conjunto
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            JOIN pcp_funcionarios f on f.id = cb.id_funcionario
            where
                cb.id_producao = :idProducao
                and cb.estornado = "Y"
            ORDER BY cb.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idProducao', $request['idProducao']);
        $stmt->execute();
        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'producao' => array(
                    'id' => $row->id_producao,
                    'nome' => $row->nome_producao
                ),
                'produto' => array(
                    'id' => $row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto
                ),
                'conjunto' => array(
                    'id' => $row->id_conjunto,
                    'nome' => $row->nome_conjunto
                ),
                'setor' => array(
                    'id' => $row->id_setor,
                    'nome' => $row->nome_setor
                ),
                'subproduto' => array(
                    'id' => $row->id_subproduto,
                    'nome' => $row->nome_subproduto,
                    'sequencial' => $row->sequencial
                ),
                'funcionario' => array(
                    'id' => $row->id_funcionario,
                    'nome' => $row->nome_funcionario
                ),
                'codigoDeBarras' => array(
                    'codigo' => $row->codigo,
                    'lancado' => $row->lancado,
                    'conferido' => $row->conferido
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'msg' => 'Lista de códigos de barras estornados recuperada com sucesso.',
            'payload' => $responseData
        ));
    }

    public function defeitoCodigoDeBarras($request){
        try{
            if(!$request or !is_array($request)){
                throw new \Exception('Problemas ao identificar os códigos de barras. Tente novamente.');
            }
            
            $payload = array();

            foreach($request as $key => $value){
                $todayDT = new Datetime();
                $today = $todayDT->format('Y-m-d');
                $sql = '
                    update  pcp_codigo_de_barras
                    set     defeito = "Y",
                            dt_defeito = :dataDefeito,
                            qtdeDefeito = :qtdeDefeito
                    where id = :id;
                ';
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':dataDefeito', $today);
                $stmt->bindParam(':qtdeDefeito', $value['quantidade']);
                $stmt->bindParam(':id', $value['id']);
                $stmt->execute();
                $payload = array('id' => $value['id']);
            }

            if(count($payload) > 0){
                return json_encode(array(
                    'success' => true,
                    'msg' => 'Lançamento de defeitos realizado com sucesso.',
                    'payload' => $payload
                ));
            } else {
                return json_encode(array(
                    'success' => false,
                    'msg' => 'Lançamento não efetuado. Tente novamente.',
                    'payload' => null
                ));             
            }
        } catch(\Exception $e) {
            return json_encode(array(
                'success' => false,
                'msg' => $e->getMessage()
            ));
        }
    }

    public function getCodigosDeBarrasComDefeito($request){
        $sql = '
            SELECT
                cb.id,
                cb.id_producao, pro.nome nome_producao,
                cb.id_produto, p.nome nome_produto, cor.nome cor_produto,
                cb.id_conjunto, c.nome nome_conjunto,
                cb.id_setor, s.nome nome_setor,
                cb.id_subproduto, ss.nome nome_subproduto,
                cb.id_funcionario, f.nome nome_funcionario,
                cb.sequencial, cb.codigo, cb.conferido, cb.estornado, cb.defeito
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes pro ON pro.id = cb.id_producao
            JOIN pcp_produtos p ON p.id = cb.id_produto
            JOIN pcp_cores cor on cor.id = p.id_cor
            JOIN pcp_conjuntos c ON c.id = cb.id_conjunto
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos ss ON ss.id = cb.id_subproduto
            JOIN pcp_funcionarios f on f.id = cb.id_funcionario
            where
                cb.id_producao = :idProducao
                and cb.id_setor = :idSetor
                and cb.id_subproduto = :idSubproduto
                and cb.defeito = "Y"
            ORDER BY cb.id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':idProducao', $request['idProducao']);
        $stmt->bindParam(':idSetor', $request['idSetor']);
        $stmt->bindParam(':idSubproduto', $request['idSubproduto']);
        $stmt->execute();
        $responseData = array();
        while($row = $stmt->fetch()){
            $responseData[] = array(
                'id' => (int)$row->id,
                'producao' => array(
                    'id' => $row->id_producao,
                    'nome' => $row->nome_producao
                ),
                'produto' => array(
                    'id' => $row->id_produto,
                    'nome' => $row->nome_produto,
                    'cor' => $row->cor_produto
                ),
                'conjunto' => array(
                    'id' => $row->id_conjunto,
                    'nome' => $row->nome_conjunto
                ),
                'setor' => array(
                    'id' => $row->id_setor,
                    'nome' => $row->nome_setor
                ),
                'subproduto' => array(
                    'id' => $row->id_subproduto,
                    'nome' => $row->nome_subproduto,
                    'sequencial' => $row->sequencial
                ),
                'funcionario' => array(
                    'id' => $row->id_funcionario,
                    'nome' => $row->nome_funcionario
                ),
                'codigoDeBarras' => array(
                    'codigo' => $row->codigo,
                    'conferido' => $row->conferido,
                    'estornado' => $row->estornado,
                    'defeito' => $row->lancado
                )
            );
        }

        return json_encode(array(
            'success' => true,
            'msg' => 'Lista de códigos de barras com defeito recuperado com sucesso.',
            'payload' => $responseData
        ));
    }

    public function getCodigoDeBarrasInfo($request){
        $sql = '
            SELECT
                cb.id, p.nome nome_producao, pro.nome nome_produto, cor.nome cor_produto,
                con.nome nome_conjunto, s.nome nome_setor, sp.nome nome_subproduto, f.nome nome_funcionario, cb.lancado, cb.conferido
            FROM pcp_codigo_de_barras cb
            JOIN pcp_producoes p ON p.id = cb.id_producao
            JOIN pcp_produtos pro ON pro.id = cb.id_produto
            JOIN pcp_cores cor ON cor.id = pro.id_cor
            JOIN pcp_conjuntos con ON con.id = cb.id_conjunto
            JOIN pcp_setores s ON s.id = cb.id_setor
            JOIN pcp_subprodutos sp ON sp.id = cb.id_subproduto
            LEFT JOIN pcp_funcionarios f ON f.id = cb.id_funcionario
            WHERE cb.codigo = :codigo;
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':codigo', $request['codigo']);
        $stmt->execute();
        $row = $stmt->fetch();
        $responseData = array(
            'idProducao' => $row->id,
            'nomeProducao' => $row->nome_producao,
            'nomeProduto' => $row->nome_produto,
            'corProduto' => $row->cor_produto,
            'nomeConjunto' => $row->nome_conjunto,
            'nomeSetor' => $row->nome_setor,
            'nomeSubproduto' => $row->nome_subproduto,
            'nomeFuncionario' => $row->nome_funcionario,
            'lancado' => $row->lancado,
            'conferido' => $row->conferido,
            'codigo' => $request['codigo']
        );

        return json_encode(array(
            'success' => true,
            'msg' => 'Informações de código de barras recuperadas com sucesso.',
            'payload' => $responseData
        ));
    }
}