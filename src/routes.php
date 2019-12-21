<?php
use Slim\Http\Request;
use Slim\Http\Response;
require '../classes/unidades.php';
require '../classes/setores.php';
require '../classes/subsetores.php';
require '../classes/produtos.php';
require '../classes/subprodutos.php';
require '../classes/insumos.php';
require '../classes/pedidosInsumos.php';
require '../classes/unidadesmedida.php';
require '../classes/almoxarifados.php';
require '../classes/fornecedores.php';
require '../classes/posicaoArmazem.php';
require '../classes/pedidosCompra.php';
require '../classes/entradaInsumos.php';
require '../classes/cores.php';
require '../classes/usuarios.php';
require '../classes/conjuntos.php';
require '../classes/linhasDeProducao.php';
require '../classes/producoes.php';
require '../classes/perfis.php';
require '../classes/funcionarios.php';
require '../classes/diasNaoUteis.php';
require '../classes/relatorios.php';
require '../classes/codigoDeBarras.php';
require '../classes/armazenagemInsumos.php';
require '../classes/saidaInsumos.php';
//require '../classes/wmsProdEntradas.php';
require '../classes/wmsProdArmazenagens.php';
require '../classes/wmsProdSaidas.php';
require '../classes/wmsProdAlmoxarifados.php';
require '../classes/wmsProdPosicoes.php';

// Routes
/************************ GET ************************/
/* Unidades */
$app->get('/getUnidades', function (Request $request, Response $response){
    $classUnidades = new Unidades($this->db);
    return $response->write($classUnidades->getUnidades($request->getQueryParams()));
});

$app->get('/deleteUnidade', function (Request $request, Response $response){
    $classUnidades = new Unidades($this->db);
    return $response->write($classUnidades->deleteUnidade($request->getQueryParams()));
});

/* Setores */
$app->get('/getSetores', function (Request $request, Response $response){
    $classSetores = new Setores($this->db);
    return $response->write($classSetores->getSetores($request->getQueryParams()));
});

$app->get('/deleteSetor', function (Request $request, Response $response){
    $classSetores = new Setores($this->db);
    return $response->write($classSetores->deleteSetor($request->getQueryParams()));
});

$app->get('/getSetoresTitulo', function (Request $request, Response $response){
    $classSetores = new Setores($this->db);
    return $response->write($classSetores->getSetoresTitulo($request->getQueryParams()));
});

/* Subsetores */
$app->get('/getSubsetores', function (Request $request, Response $response){
    $classSubsetores = new Subsetores($this->db);
    return $response->write($classSubsetores->getSubsetores($request->getQueryParams()));
});

$app->get('/deleteSubsetor', function (Request $request, Response $response){
    $classSubsetores = new Subsetores($this->db);
    return $response->write($classSubsetores->deleteSubsetor($request->getQueryParams()));
});

/* Produtos */
$app->get('/getProdutos', function (Request $request, Response $response){
    $classProdutos = new Produtos($this->db);
    return $response->write($classProdutos->getProdutos($request->getQueryParams()));
});

$app->get('/getProdutosFull', function (Request $request, Response $response){
    $classProdutos = new Produtos($this->db);
    return $response->write($classProdutos->getProdutosFull($request->getQueryParams()));
});

$app->get('/deleteProduto', function (Request $request, Response $response){
    $classProdutos = new Produtos($this->db);
    return $response->write($classProdutos->deleteProduto($request->getQueryParams()));
});

/* Subprodutos */
$app->get('/getSubprodutos', function (Request $request, Response $response){
    $classSubprodutos = new Subprodutos($this->db);
    return $response->write($classSubprodutos->getSubprodutos($request->getQueryParams()));
});

$app->get('/deleteSubproduto', function (Request $request, Response $response){
    $classSubprodutos = new Subprodutos($this->db);
    return $response->write($classSubprodutos->deleteSubproduto($request->getQueryParams()));
});

$app->get('/getSubprodutosPorProducaoSetor', function (Request $request, Response $response){
    $classSubprodutos = new Subprodutos($this->db);
    return $response->write($classSubprodutos->getSubprodutosPorProducaoSetor($request->getQueryParams()));
});

/* Cores */
$app->get('/getCores', function (Request $request, Response $response){
    $classCores = new Cores($this->db);
    return $response->write($classCores->getCores($request->getQueryParams()));
});

$app->get('/deleteCor', function (Request $request, Response $response){
    $classCores = new Cores($this->db);
    return $response->write($classCores->deleteCor($request->getQueryParams()));
});

/* Usuários */
$app->get('/getUsuarios', function (Request $request, Response $response){
    $classUsuarios = new Usuarios($this->db);
    return $response->write($classUsuarios->getUsuarios($request->getQueryParams()));
});

$app->get('/deleteUsuario', function (Request $request, Response $response){
    $classUsuarios = new Usuarios($this->db);
    return $response->write($classUsuarios->deleteUsuario($request->getQueryParams()));
});

$app->get('/logout', function (Request $request, Response $response){
    $classUsuarios = new Usuarios($this->db);
    return $response->write($classUsuarios->logout($request->getQueryParams()));
});

/* Conjuntos */
$app->get('/getConjuntos', function (Request $request, Response $response){
    $classConjuntos = new Conjuntos($this->db);
    return $response->write($classConjuntos->getConjuntos($request->getQueryParams()));
});

$app->get('/deleteConjunto', function (Request $request, Response $response){
    $classConjuntos = new Conjuntos($this->db);
    return $response->write($classConjuntos->deleteConjunto($request->getQueryParams()));
});

/* Linhas de Produção */
$app->get('/getLinhasDeProducao', function (Request $request, Response $response){
    $classLinhasDeProducao = new LinhasDeProducao($this->db);
    return $response->write($classLinhasDeProducao->getLinhasDeProducao($request->getQueryParams()));
});

$app->get('/deleteLinhaDeProducao', function (Request $request, Response $response){
    $classLinhasDeProducao = new LinhasDeProducao($this->db);
    return $response->write($classLinhasDeProducao->deleteLinhaDeProducao($request->getQueryParams()));
});

/* Setores por Linha de Produção */
$app->get('/getSetoresPorLinhaDeProducao', function (Request $request, Response $response){
    $classLinhasDeProducao = new LinhasDeProducao($this->db);
    return $response->write($classLinhasDeProducao->getSetoresPorLinhaDeProducao($request->getQueryParams()));
});

/* Produção */
$app->get('/getProducoesTitulo', function (Request $request, Response $response){
    $classProducoes= new Producoes($this->db);
    return $response->write($classProducoes->getProducoesTitulo($request->getQueryParams()));
});

$app->get('/getProducoes', function (Request $request, Response $response){
    $classProducoes= new Producoes($this->db);
    return $response->write($classProducoes->getProducoes($request->getQueryParams()));
});

$app->get('/getProducaoAcompanhamento', function (Request $request, Response $response){
    $classProducoes= new Producoes($this->db);
    return $response->write($classProducoes->getProducaoAcompanhamento($request->getQueryParams()));
});

/* Perfis */
$app->get('/getPerfis', function (Request $request, Response $response){
    $classPerfis = new Perfis($this->db);
    return $response->write($classPerfis->getPerfis($request->getQueryParams()));
});

$app->get('/deletePerfil', function (Request $request, Response $response){
    $classPerfis = new Perfis($this->db);
    return $response->write($classPerfis->deletePerfil($request->getQueryParams()));
});

/* Funcionários */
$app->get('/getFuncionarios', function (Request $request, Response $response){
    $classFuncionarios = new Funcionarios($this->db);
    return $response->write($classFuncionarios->getFuncionarios($request->getQueryParams()));
});

$app->get('/deleteFuncionario', function (Request $request, Response $response){
    $classFuncionarios = new Funcionarios($this->db);
    return $response->write($classFuncionarios->deleteFuncionario($request->getQueryParams()));
});

/* Dias não Úteis */
$app->get('/getDiasNaoUteis', function (Request $request, Response $response){
    $classDiasNaoUteis= new DiasNaoUteis($this->db);
    return $response->write($classDiasNaoUteis->getDiasNaoUteis($request->getQueryParams()));
});

$app->get('/deleteDiaNaoUtil', function (Request $request, Response $response){
    $classDiasNaoUteis = new DiasNaoUteis($this->db);
    return $response->write($classDiasNaoUteis->deleteDiaNaoUtil($request->getQueryParams()));
});

/* Insumos */
$app->get('/getInsumos', function (Request $request, Response $response){
    $classInsumos = new Insumos($this->db);
    return $response->write($classInsumos->getInsumos($request->getQueryParams()));
});

$app->get('/deleteInsumo', function (Request $request, Response $response){
    $classInsumos = new Insumos($this->db);
    return $response->write($classInsumos->deleteInsumo($request->getQueryParams()));
});

/* Unidades de Medida */
$app->get('/getUnidadesMedida', function (Request $request, Response $response){
    $classUnidadesMedida = new UnidadesMedida($this->db);
    return $response->write($classUnidadesMedida->getUnidadesMedida($request->getQueryParams()));
});

$app->get('/deleteUnidadeMedida', function (Request $request, Response $response){
    $classUnidadesMedida = new UnidadesMedida($this->db);
    return $response->write($classUnidadesMedida->deleteUnidadeMedida($request->getQueryParams()));
});

/* Fornecedores */
$app->get('/getFornecedores', function (Request $request, Response $response){
    $classFornecedores = new Fornecedores($this->db);
    return $response->write($classFornecedores->getFornecedores($request->getQueryParams()));
});

$app->get('/deleteFornecedor', function (Request $request, Response $response){
    $classFornecedores = new Fornecedores($this->db);
    return $response->write($classFornecedores->deleteFornecedor($request->getQueryParams()));
});

/* Almoxarifados */
$app->get('/getAlmoxarifados', function (Request $request, Response $response){
    $classAlmoxarifados = new Almoxarifados($this->db);
    return $response->write($classAlmoxarifados->getAlmoxarifados($request->getQueryParams()));
});

$app->get('/deleteAlmoxarifado', function (Request $request, Response $response){
    $classAlmoxarifados = new Almoxarifados($this->db);
    return $response->write($classAlmoxarifados->deleteAlmoxarifado($request->getQueryParams()));
});

/* Posição do Armazém */
$app->get('/getPosicaoArmazens', function (Request $request, Response $response){
    $classPosicaoArmazem = new PosicaoArmazem($this->db);
    return $response->write($classPosicaoArmazem->getPosicaoArmazens($request->getQueryParams()));
});

$app->get('/deletePosicaoArmazem', function (Request $request, Response $response){
    $classPosicaoArmazem = new PosicaoArmazem($this->db);
    return $response->write($classPosicaoArmazem->deletePosicaoArmazem($request->getQueryParams()));
});

/* Pedidos de Compra */
$app->get('/getPedidosCompra', function (Request $request, Response $response){
    $classPedidosCompra = new PedidosCompra($this->db);
    return $response->write($classPedidosCompra->getPedidosCompra($request->getQueryParams()));
});

$app->get('/getPedidosCompraInsumos', function (Request $request, Response $response){
    $classPedidosCompra = new PedidosCompra($this->db);
    return $response->write($classPedidosCompra->getPedidosCompraInsumos($request->getQueryParams()));
});

$app->get('/deletePedidoCompra', function (Request $request, Response $response){
    $classPedidosCompra = new PedidosCompra($this->db);
    return $response->write($classPedidosCompra->deletePedidoCompra($request->getQueryParams()));
});

$app->get('/printPedidoCompra', function (Request $request, Response $response){
    $classPedidosCompra = new PedidosCompra($this->db);
    return $response->write($classPedidosCompra->printPedidoCompra($request->getQueryParams()));
});

/* Entrada de insumos */
$app->get('/getEntradaInsumos', function (Request $request, Response $response){
    $classEntradaInsumos = new EntradaInsumos($this->db);
    return $response->write($classEntradaInsumos->getEntradaInsumos($request->getQueryParams()));
});

$app->get('/deleteEntrada', function (Request $request, Response $response){
    $classEntradaInsumos = new EntradaInsumos($this->db);
    return $response->write($classEntradaInsumos->deleteEntrada($request->getQueryParams()));
});

/* Armazenagem de Insumos */
$app->get('/getArmazenagens', function (Request $request, Response $response){
    $classArmazenagemInsumos = new ArmazenagemInsumos($this->db);
    return $response->write($classArmazenagemInsumos->getArmazenagens($request->getQueryParams()));
});

$app->get('/getInsumosArmazenar', function (Request $request, Response $response){
    $classArmazenagemInsumos = new ArmazenagemInsumos($this->db);
    return $response->write($classArmazenagemInsumos->getInsumosArmazenar($request->getQueryParams()));
});

$app->get('/getInsumosArmazenados', function (Request $request, Response $response){
    $classArmazenagemInsumos = new ArmazenagemInsumos($this->db);
    return $response->write($classArmazenagemInsumos->getInsumosArmazenados($request->getQueryParams()));
});

$app->get('/deleteArmazenagem', function (Request $request, Response $response){
    $classArmazenagemInsumos = new ArmazenagemInsumos($this->db);
    return $response->write($classArmazenagemInsumos->deleteArmazenagem($request->getQueryParams()));
});

/* Saída de Insumos */
$app->get('/getSaidas', function (Request $request, Response $response){
    $classSaidaInsumos = new SaidaInsumos($this->db);
    return $response->write($classSaidaInsumos->getSaidas($request->getQueryParams()));
});

$app->get('/getInsumosDisponiveisParaSaida', function (Request $request, Response $response){
    $classSaidaInsumos = new SaidaInsumos($this->db);
    return $response->write($classSaidaInsumos->getInsumosDisponiveisParaSaida($request->getQueryParams()));
});

$app->get('/getInsumosRetirados', function (Request $request, Response $response){
    $classSaidaInsumos = new SaidaInsumos($this->db);
    return $response->write($classSaidaInsumos->getInsumosRetirados($request->getQueryParams()));
});

$app->get('/deleteSaida', function (Request $request, Response $response){
    $classSaidaInsumos = new SaidaInsumos($this->db);
    return $response->write($classSaidaInsumos->deleteSaida($request->getQueryParams()));
});

/* Relatórios */
$app->get('/reportProdutosCadastrados', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportProdutosCadastrados($request->getQueryParams()));
});

$app->get('/reportFuncionariosCadastrados', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportFuncionariosCadastrados($request->getQueryParams()));
});

$app->get('/reportProducoes', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportProducoes($request->getQueryParams()));
});

$app->get('/reportFuncionariosPontuacoes', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportBonusPontuacao($request->getQueryParams()));
});

$app->get('/reportGeralLancamentoProducao', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportGeralLancamentoProducao($request->getQueryParams()));
});

$app->get('/reportNaoProduzidos', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportNaoProduzidos($request->getQueryParams()));
});

$app->get('/reportEntradaInsumos', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportEntradaInsumos($request->getQueryParams()));
});

$app->get('/reportArmazenagemInsumos', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportArmazenagemInsumos($request->getQueryParams()));
});

$app->get('/reportSaidaInsumos', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportSaidaInsumos($request->getQueryParams()));
});

$app->get('/reportEstoqueProdutos', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportEstoqueProdutos($request->getQueryParams()));
});

$app->get('/reportSaidaProdutos', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->reportSaidaProdutos($request->getQueryParams()));
});

$app->get('/insertId', function (Request $request, Response $response){
    $classRelatorios = new Relatorios($this->db, $this->spreadsheet, $this->writer);
    return $response->write($classRelatorios->insertId($request->getQueryParams()));
});


/* Código de Barras */
$app->get('/gerarCodigosDeBarrasCSV', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->gerarCodigosDeBarrasCSV($request->getQueryParams()));
});

$app->get('/gerarCodigosDeBarras', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->gerarCodigosDeBarras($request->getQueryParams()));
});

$app->get('/getCodigosDeBarrasLancados', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->getCodigosDeBarrasLancados($request->getQueryParams()));
});

$app->get('/getCodigosDeBarrasProducao', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->getCodigosDeBarrasProducao($request->getQueryParams()));
});

$app->get('/getCodigoDeBarra', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->getCodigoDeBarra($request->getQueryParams()));
});

$app->get('/getCodigosDeBarrasEstornados', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->getCodigosDeBarrasEstornados($request->getQueryParams()));
});

$app->get('/getCodigosDeBarrasComDefeito', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->getCodigosDeBarrasComDefeito($request->getQueryParams()));
});

$app->get('/getCodigoDeBarrasInfo', function (Request $request, Response $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->getCodigoDeBarrasInfo($request->getQueryParams()));
});

$app->get('/getPedidosCompraAvailabes', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->getPedidosCompraAvailabes($request->getQueryParams()));
});

$app->get('/getPedidosCompraInsumosAvailabes', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->getPedidosCompraInsumosAvailabes($request->getQueryParams()));
});

$app->get('/getPedidosInsumosAvailabes', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->getPedidosInsumosAvailabes($request->getQueryParams()));
});

$app->get('/getPedidosInsumos', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->getPedidosInsumos($request->getQueryParams()));
});

$app->get('/getInsumosAvailabesToEnter', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->getInsumosAvailabesToEnter($request->getQueryParams()));
});

$app->get('/getPedidosCompraInsumosArmazenarAvailables', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->getPedidosCompraInsumosArmazenarAvailables($request->getQueryParams()));
});

/* WMS Produtos */
/* Almoxarifados */
$app->get('/wms-produtos/getAlmoxarifados', function (Request $request, Response $response){
    $classAlmoxarifadosWMSProd = new WMSProdAlmoxasrifados($this->db);
    return $response->write($classAlmoxarifadosWMSProd->getAlmoxarifados($request->getQueryParams()));
});

$app->get('/wms-produtos/deleteAlmoxarifado', function (Request $request, Response $response){
    $classAlmoxarifadosWMSProd = new WMSProdAlmoxasrifados($this->db);
    return $response->write($classAlmoxarifadosWMSProd->deleteAlmoxarifado($request->getQueryParams()));
});

/* Posições */
$app->get('/wms-produtos/getPosicoes', function (Request $request, Response $response){
    $classPosicoesWMSProd = new WMSProdPosicoes($this->db);
    return $response->write($classPosicoesWMSProd->getPosicoes($request->getQueryParams()));
});

$app->get('/wms-produtos/deletePosicao', function (Request $request, Response $response){
    $classPosicoesWMSProd = new WMSProdPosicoes($this->db);
    return $response->write($classPosicoesWMSProd->deletePosicao($request->getQueryParams()));
});


/* Entrada */
/*
$app->get('/wms-produtos/getEntradas', function (Request $request, Response $response){
    $classEntradaProdutosFinalizados = new WMSProdEntradas($this->db);
    return $response->write($classEntradaProdutosFinalizados->getEntradas($request->getQueryParams()));
});

$app->get('/wms-produtos/getEntradaProdutos', function (Request $request, Response $response){
    $classEntradaProdutosFinalizados = new WMSProdEntradas($this->db);
    return $response->write($classEntradaProdutosFinalizados->getEntradaProdutos($request->getQueryParams()));
});
*/

/* Armazenagem */
$app->get('/wms-produtos/getArmazenagens', function (Request $request, Response $response){
    $classArmazenagemProdutosFinalizados = new WMSProdArmazenagens($this->db);
    return $response->write($classArmazenagemProdutosFinalizados->getArmazenagens($request->getQueryParams()));
});

$app->get('/wms-produtos/getArmazenagemProdutos', function (Request $request, Response $response){
    $classArmazenagemProdutosFinalizados = new WMSProdArmazenagens($this->db);
    return $response->write($classArmazenagemProdutosFinalizados->getArmazenagemProdutos($request->getQueryParams()));
});

/* Saída */
$app->get('/wms-produtos/getSaidas', function (Request $request, Response $response){
    $WMSProdSaidas = new WMSProdSaidas($this->db);
    return $response->write($WMSProdSaidas->getSaidas($request->getQueryParams()));
});

$app->get('/wms-produtos/getSaidaProdutos', function (Request $request, Response $response){
    $WMSProdSaidas = new WMSProdSaidas($this->db);
    return $response->write($WMSProdSaidas->getSaidaProdutos($request->getQueryParams()));
});

$app->get('/wms-produtos/deleteSaida', function (Request $request, Response $response){
    $WMSProdSaidas = new WMSProdSaidas($this->db);
    return $response->write($WMSProdSaidas->deleteSaida($request->getQueryParams()));
});

$app->get('/wms-produtos/deleteSaidaProdutos', function (Request $request, Response $response){
    $WMSProdSaidas = new WMSProdSaidas($this->db);
    return $response->write($WMSProdSaidas->deleteSaidaProdutos($request->getQueryParams()));
});

/*****************************************************/
/*****************************************************/
/*****************************************************/
/*****************************************************/
/*****************************************************/
/*****************************************************/

/************************ POST ************************/
/* Unidades */
$app->post('/createUpdateUnidade', function ($request, $response){
    $classUnidades = new Unidades($this->db);
    return $response->write($classUnidades->createUpdateUnidade(json_decode($request->getBody(), true)));
});

/* Setores */
$app->post('/createUpdateSetor', function ($request, $response){
    $classSetores = new Setores($this->db);
    return $response->write($classSetores->createUpdateSetor(json_decode($request->getBody(), true)));
});

/* Subsetores */
$app->post('/createUpdateSubsetor', function ($request, $response){
    $classSubsetores = new Subsetores($this->db);
    return $response->write($classSubsetores->createUpdateSubsetor(json_decode($request->getBody(), true)));
});

/* Produtos */
$app->post('/createUpdateProduto', function ($request, $response){
    $classProdutos = new Produtos($this->db);
    return $response->write($classProdutos->createUpdateProduto(json_decode($request->getBody(), true)));
});

/* Subprodutos */
$app->post('/createUpdateSubproduto', function ($request, $response){
    $classSubprodutos = new Subprodutos($this->db);
    return $response->write($classSubprodutos->createUpdateSubproduto(json_decode($request->getBody(), true)));
});

/* Cores */
$app->post('/createUpdateCor', function ($request, $response){
    $classCores = new Cores($this->db);
    return $response->write($classCores->createUpdateCor(json_decode($request->getBody(), true)));
});

/* Usuários */
$app->post('/createUpdateUsuario', function ($request, $response){
    $classUsuarios = new Usuarios($this->db);
    return $response->write($classUsuarios->createUpdateUsuario(json_decode($request->getBody(), true)));
});

$app->post('/login', function ($request, $response){
    $classUsuarios = new Usuarios($this->db);
    return $response->write($classUsuarios->login(json_decode($request->getBody(), true)));
});

/* Conjuntos */
$app->post('/createUpdateConjunto', function ($request, $response){
    $classConjuntos = new Conjuntos($this->db);
    return $response->write($classConjuntos->createUpdateConjunto(json_decode($request->getBody(), true)));
});

/* Linhas de Produção */
$app->post('/createUpdateLinhaDeProducao', function ($request, $response){
    $classLinhasDeProducao = new LinhasDeProducao($this->db);
    return $response->write($classLinhasDeProducao->createUpdateLinhaDeProducao(json_decode($request->getBody(), true)));
});

/* Produções */
$app->post('/createUpdateProducao', function ($request, $response){
    $classProducoes = new Producoes($this->db);
    return $response->write($classProducoes->createUpdateProducao(json_decode($request->getBody(), true)));
});

$app->post('/deleteProducao', function ($request, $response){
    $classProducoes = new Producoes($this->db);
    return $response->write($classProducoes->deleteProducao(json_decode($request->getBody(), true)));
});

$app->post('/updateRealizadoQuantidade', function ($request, $response){
    $classProducoes = new Producoes($this->db);
    return $response->write($classProducoes->updateRealizadoQuantidade(json_decode($request->getBody(), true)));
});

/* Perfis */
$app->post('/createUpdatePerfil', function ($request, $response){
    $classPerfis = new Perfis($this->db);
    return $response->write($classPerfis->createUpdatePerfil(json_decode($request->getBody(), true)));
});

/* Funcionários */
$app->post('/createUpdateFuncionario', function ($request, $response){
    $classFuncionarios = new Funcionarios($this->db);
    return $response->write($classFuncionarios->createUpdateFuncionario(json_decode($request->getBody(), true)));
});

/* Dias não Úteis */
$app->post('/createUpdateDiaNaoUtil', function ($request, $response){
    $classDiasNaoUteis = new DiasNaoUteis($this->db);
    return $response->write($classDiasNaoUteis->createUpdateDiaNaoUtil(json_decode($request->getBody(), true)));
});

/* Insumos */
$app->post('/createUpdateInsumo', function ($request, $response){
    $classInsumos = new Insumos($this->db);
    return $response->write($classInsumos->createUpdateInsumo(json_decode($request->getBody(), true)));
});

$app->post('/importInsumos', function(Request $request, Response $response) {
    $classInsumos = new Insumos($this->db);
    return $response->write($classInsumos->importInsumos($this->get('upload_directory'), $request->getUploadedFiles()));
});

/* Unidades de Medida */
$app->post('/createUpdateUnidadeMedida', function ($request, $response){
    $classUnidadesMedida = new UnidadesMedida($this->db);
    return $response->write($classUnidadesMedida->createUpdateUnidadeMedida(json_decode($request->getBody(), true)));
});

/* Fornecedores */
$app->post('/createUpdateFornecedor', function ($request, $response){
    $classFornecedores = new Fornecedores($this->db);
    return $response->write($classFornecedores->createUpdateFornecedor(json_decode($request->getBody(), true)));
});

/* Almoxarifados */
$app->post('/createUpdateAlmoxarifado', function ($request, $response){
    $classAlmoxarifados = new Almoxarifados($this->db);
    return $response->write($classAlmoxarifados->createUpdateAlmoxarifado(json_decode($request->getBody(), true)));
});

/* Posição do Armazém */
$app->post('/createUpdatePosicaoArmazem', function ($request, $response){
    $classPosicaoArmazem = new PosicaoArmazem($this->db);
    return $response->write($classPosicaoArmazem->createUpdatePosicaoArmazem(json_decode($request->getBody(), true)));
});

$app->post('/getMultiplasPosicoeArmazens', function ($request, $response){
    $classPosicaoArmazem = new PosicaoArmazem($this->db);
    return $response->write($classPosicaoArmazem->getMultiplasPosicoeArmazens(json_decode($request->getBody(), true)));
});

/* Pedidos de Compra */
$app->post('/createUpdatePedidoCompra', function ($request, $response){
    $classPedidosCompra = new PedidosCompra($this->db);
    return $response->write($classPedidosCompra->createUpdatePedidoCompra(json_decode($request->getBody(), true)));
});

/* Código de Barras */
$app->post('/lancamentoCodigoDeBarras', function ($request, $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->lancamentoCodigoDeBarras(json_decode($request->getBody(), true)));
});

$app->post('/conferenciaCodigoDeBarras', function ($request, $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->conferenciaCodigoDeBarras(json_decode($request->getBody(), true)));
});

$app->post('/estornoCodigoDeBarras', function ($request, $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->estornoCodigoDeBarras(json_decode($request->getBody(), true)));
});

$app->post('/defeitoCodigoDeBarras', function ($request, $response){
    $classCodigoDeBarras = new CodigoDeBarras($this->db);
    return $response->write($classCodigoDeBarras->defeitoCodigoDeBarras(json_decode($request->getBody(), true)));
});

$app->post('/changeStatusInsumo', function (Request $request, Response $response){
    $classPedidosInsumos = new PedidosInsumos($this->db);
    return $response->write($classPedidosInsumos->changeStatusInsumo($request->getQueryParams()));
});

/* Entrada de Insumo */
$app->post('/createUpdateEntradaInsumos', function ($request, $response){
    $classEntradaInsumos = new EntradaInsumos($this->db);
    return $response->write($classEntradaInsumos->createUpdateEntradaInsumos(json_decode($request->getBody(), true)));
});

/* Armazenagem de Insumos */
$app->post('/createUpdateArmazenagem', function ($request, $response){
    $classArmazenagemInsumos = new ArmazenagemInsumos($this->db);
    return $response->write($classArmazenagemInsumos->createUpdateArmazenagem(json_decode($request->getBody(), true)));
});

$app->post('/geracaoEtiquetasArmazenagem', function ($request, $response){
    $classArmazenagemInsumos = new ArmazenagemInsumos($this->db);
    return $response->write($classArmazenagemInsumos->geracaoEtiquetasArmazenagem(json_decode($request->getBody(), true)));
});

/* Saída de Insumos */
$app->post('/createUpdateSaida', function ($request, $response){
    $classSaidaInsumos = new SaidaInsumos($this->db);
    return $response->write($classSaidaInsumos->createUpdateSaida(json_decode($request->getBody(), true)));
});

/* WMS Produtos Finalizados */
/* Almoxarifados */
$app->post('/wms-produtos/createUpdateAlmoxarifado', function ($request, $response){
    $classAlmoxarifadosWMSProd = new WMSProdAlmoxasrifados($this->db);
    return $response->write($classAlmoxarifadosWMSProd->createUpdateAlmoxarifado(json_decode($request->getBody(), true)));
});

/* Posições */
$app->post('/wms-produtos/createUpdatePosicao', function ($request, $response){
    $classPosicoesWMSProd = new WMSProdPosicoes($this->db);
    return $response->write($classPosicoesWMSProd->createUpdatePosicao(json_decode($request->getBody(), true)));
});

/* Entrada */
$app->post('/wms-produtos/lancamentoEntradaProdutos', function ($request, $response){
    $classEntradaProdutosFinalizados = new EntradaProdutosFinalizados($this->db);
    return $response->write($classEntradaProdutosFinalizados->lancamentoEntradaProdutos(json_decode($request->getBody(), true)));
});

/* Armazenagem */
$app->post('/wms-produtos/lancamentoArmazenagemProdutos', function ($request, $response){
    $classArmazenagemProdutos = new WMSProdArmazenagens($this->db);
    return $response->write($classArmazenagemProdutos->lancamentoArmazenagemProdutos(json_decode($request->getBody(), true)));
});

/* Saída */
$app->post('/wms-produtos/lancamentoSaidaProdutos', function (Request $request, Response $response){
    $WMSProdSaidas = new WMSProdSaidas($this->db);
    return $response->write($WMSProdSaidas->lancamentoSaidaProdutos(json_decode($request->getBody(), true)));
});

/******************************************************/