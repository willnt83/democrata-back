<?php
class CodigoDeBarras{
    public function __construct($db){
        $this->pdo = $db;
        //$this->spreadsheet = $spreadsheet;
        //$this->writer = $writer;
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
                order by lps.ordem, pa.id_setor, ss.nome, pa.id_produto;
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
                    //$output[] = array(utf8_decode($row->nome_setor), '');
                    $output[] = array(utf8_decode($row->nome_setor), '');
                    $lastSetor = $row->id_setor;
                }

                $pontos = $row->pontos == null ? 0 : $row->pontos;
                if($row->total_quantidade > 1){
                    $i = 1;
                    while($i <= $row->total_quantidade){
                        $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-".$i;
                        //$output[] = array(utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                        $output[] = array(utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                        $insertData[] = '('.$row->id_producao.', '.$row->id_produto.', '.$row->id_conjunto.', '.$row->id_setor.', '.$row->id_subproduto.', '.$i.', "'.$barCode.'", "'.$currDate.'", '.$pontos.')';
                        $i++;
                    }
                }
                else{
                    $barCode = $row->id_producao."-".$row->id_produto."-".$row->id_conjunto."-".$row->id_setor."-".$row->id_subproduto."-1";
                    //$output[] = array(utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);
                    $output[] = array(utf8_decode($row->nome_produto.'-'.$row->cor_produto.'-'.$row->nome_subproduto), $barCode);

                    $insertData[] = '('.$row->id_producao.', '.$row->id_produto.', '.$row->id_conjunto.', '.$row->id_setor.', '.$row->id_subproduto.', 1, "'.$barCode.'", "'.$currDate.'", '.$pontos.')';
                }
            }

            // Verificação de código de barras já existente na tabela pcp_codigo_de_barras
            $sqlVer = '
                select count(*) count
                from pcp_codigo_de_barras
                where
                    id_producao = :idProducao;
            ';
            $stmtVer = $this->pdo->prepare($sqlVer);
            $stmtVer->bindParam(':idProducao', $filters['id_producao']);
            $stmtVer->execute();
            $rowCount = $stmtVer->fetch();

            // Não há registros em pcp_codigo_de_barras
            if($rowCount->count == 0){
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
            $fp = fopen('barcodes/'.$filters['id_producao'].'/producao-'.$filters['id_producao'].'.csv', 'w');

            foreach ($output as $fields) {
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
                order by lps.ordem, pa.id_setor, pa.id_subproduto, pa.id;
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


            // Geração do PDF
            $i = 0;
            $pdf = new FPDF();
            $lastSetor = null;
            while($i < count($barCodes)){
                // Título do Setor
                if($barCodes[$i]['setor'] != $lastSetor){
                    $pdf->AddPage();
                    $pdf->SetFont('Arial','B',12);
                    $pdf->Cell( 188, 7, utf8_decode($barCodes[$i]['setor']).' - '.$this->sql2date($barCodes[$i]['dataInicial']), 1, 0, 'C', false);
                    $pdf->SetFont('Arial','B',8);
                    $pdf->Ln();
                    $lastSetor = $barCodes[$i]['setor'];
                }

                // Impar
                if($i % 2 == 0){
                    // Armazena os dados em vetor auxiliar
                    $aux = $barCodes[$i];
                }
                // Par
                else{
                    // Impressao
                    // Gerando a string base64 da imagem
                    $strBase64Aux = 'data:image/png;base64,'.base64_encode($generator->getBarcode($aux['strBase64'], $generator::TYPE_CODE_128));
                    $strBase64 = 'data:image/png;base64,'.base64_encode($generator->getBarcode($barCodes[$i]['strBase64'], $generator::TYPE_CODE_128));
                    // Gerando o arquivo de imagem
                    $imgAux = $this->generateImage($strBase64Aux, $aux['file']);
                    $img = $this->generateImage($strBase64, $barCodes[$i]['file']);

                    $pdf->SetFont('Arial', '', 5);
                    $pdf->Cell(47, 4, utf8_decode($aux['nomeProduto']).' ('.utf8_decode($aux['corProduto']).')', 'LTR', 0, 'C');
                    $pdf->Cell(47, 4, utf8_decode($aux['nomeProduto']).' ('.utf8_decode($aux['corProduto']).')', 'LTR', 0, 'C');

                    $pdf->Cell(47, 4, utf8_decode($barCodes[$i]['nomeProduto']).' ('.utf8_decode($aux['corProduto']).')', 'LTR', 0, 'C');
                    $pdf->Cell(47, 4, utf8_decode($barCodes[$i]['nomeProduto']).' ('.utf8_decode($aux['corProduto']).')', 'LTR', 0, 'C');
                    $pdf->Ln();

                    $pdf->Cell(47, 4, utf8_decode($aux['nomeSubproduto']), 'LRB', 0, 'C');
                    $pdf->Cell(47, 4, utf8_decode($aux['nomeSubproduto']), 'LRB', 0, 'C');
                    $pdf->Cell(47, 4, utf8_decode($aux['nomeSubproduto']), 'LRB', 0, 'C');
                    $pdf->Cell(47, 4, utf8_decode($aux['nomeSubproduto']), 'LRB', 0, 'C');
                    
                    $pdf->SetFont('Arial','', 7);
                    $pdf->Ln();
                    $pdf->Cell(47, 12, $pdf->Image($imgAux, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTR', 0, 'C', false);
                    $pdf->Cell(47, 12, $pdf->Image($imgAux, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTR', 0, 'C', false);
                    $pdf->Cell(47, 12, $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTR', 0, 'C', false);
                    $pdf->Cell(47, 12, $pdf->Image($img, $pdf->GetX()+3, $pdf->GetY()+2, 40, 8), 'LTR', 0, 'C', false);
                    $pdf->Ln();

                    $pdf->Cell(47, 3, $aux['strBase64'], 'LRB', 0, 'C', false);
                    $pdf->Cell(47, 3, $aux['strBase64'], 'LRB', 0, 'C', false);
                    $pdf->Cell(47, 3, $barCodes[$i]['strBase64'], 'LRB', 0, 'C', false);
                    $pdf->Cell(47, 3, $barCodes[$i]['strBase64'], 'LRB', 0, 'C', false);
                    $pdf->Ln();

                    $pdf->Cell(188, 6, '', 0, 0, 'C', false);
                    $pdf->Ln();
                }
                $i++;
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
        $sql = '
            select id, lancado
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

                $success = true;
                $msg = 'Código de barras registrado com sucesso.';
            }
            else{
                $success = false;
                $msg = 'Código de barras já lançado.';
            }
        }
        else{
            $success = false;
            $msg = 'Código de barras inexistente.';
        }

        return json_encode(array(
            'success' => $success,
            'msg' => $msg
        ));
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
}