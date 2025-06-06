<?php
/**
 * Processador de uploads de arquivos
 * Contém as funções para processar e salvar arquivos enviados
 */

class UploadProcessor {
    private $config;
    
    public function __construct($config = null) {
        $this->config = $config ?: [
            'max_file_size' => 50 * 1024 * 1024, // 50MB por arquivo
            'allowed_types' => [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
                'application/pdf'
            ],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf'],
            'upload_dir' => '../uploads/',
            'field_name' => 'arquivos'
        ];
    }
    
    /**
     * Processa múltiplos arquivos enviados via formulário
     */
    public function processarMultiplosArquivos($projeto_id, $titulo_projeto = '', $conn = null) {
        $field_name = $this->config['field_name'];
        
        if (empty($_FILES[$field_name]['name'][0])) {
            return [
                'sucesso' => true,
                'arquivos_processados' => 0,
                'erros' => [],
                'mensagem' => 'Nenhum arquivo enviado.'
            ];
        }
        
        // Criar nome do diretório baseado no título do projeto
        $nome_diretorio = $this->sanitizarNomeDiretorio($titulo_projeto);
        
        // Se após sanitização o nome ficar vazio, usar fallback com ID
        if (empty($nome_diretorio)) {
            $nome_diretorio = "projeto_" . $projeto_id;
        } else {
            // Adicionar ID ao final para garantir unicidade
            $nome_diretorio = $nome_diretorio . "_" . $projeto_id;
        }
        
        // Criar diretório específico para o projeto
        $diretorio_projeto = $this->config['upload_dir'] . $nome_diretorio;
        $this->criarDiretorio($diretorio_projeto);
        
        $arquivos_processados = 0;
        $erros_upload = [];
        $total_arquivos = count($_FILES[$field_name]['name']);
        
        for ($i = 0; $i < $total_arquivos; $i++) {
            // Montar array do arquivo individual
            $arquivo = [
                'name' => $_FILES[$field_name]['name'][$i],
                'type' => $_FILES[$field_name]['type'][$i],
                'tmp_name' => $_FILES[$field_name]['tmp_name'][$i],
                'error' => $_FILES[$field_name]['error'][$i],
                'size' => $_FILES[$field_name]['size'][$i]
            ];
            
            // Pular arquivos vazios
            if (empty($arquivo['name'])) continue;
            
            // Validar arquivo
            $erros_validacao = $this->validarArquivo($arquivo);
            
            if (!empty($erros_validacao)) {
                $erros_upload[] = $arquivo['name'] . ": " . implode(", ", $erros_validacao);
                continue;
            }
            
            // Processar arquivo individual
            $resultado = $this->processarArquivoIndividual($arquivo, $diretorio_projeto, $nome_diretorio, $projeto_id, $conn);
            
            if ($resultado['sucesso']) {
                $arquivos_processados++;
            } else {
                $erros_upload[] = $arquivo['name'] . ": " . $resultado['erro'];
            }
        }
        
        return [
            'sucesso' => $arquivos_processados > 0 || empty($erros_upload),
            'arquivos_processados' => $arquivos_processados,
            'erros' => $erros_upload,
            'mensagem' => $this->gerarMensagemResultado($arquivos_processados, $erros_upload)
        ];
    }
    
    /**
     * Processa um único arquivo
     */
    public function processarArquivoUnico($projeto_id, $titulo_projeto = '', $conn = null, $field_name = null) {
        $field_name = $field_name ?: $this->config['field_name'];
        
        if (empty($_FILES[$field_name]['name'])) {
            return [
                'sucesso' => false,
                'erro' => 'Nenhum arquivo enviado.',
                'arquivo_info' => null
            ];
        }
        
        $arquivo = $_FILES[$field_name];
        
        // Validar arquivo
        $erros_validacao = $this->validarArquivo($arquivo);
        if (!empty($erros_validacao)) {
            return [
                'sucesso' => false,
                'erro' => implode(", ", $erros_validacao),
                'arquivo_info' => null
            ];
        }
        
        // Criar diretório
        $nome_diretorio = $this->sanitizarNomeDiretorio($titulo_projeto);
        if (empty($nome_diretorio)) {
            $nome_diretorio = "projeto_" . $projeto_id;
        } else {
            $nome_diretorio = $nome_diretorio . "_" . $projeto_id;
        }
        
        $diretorio_projeto = $this->config['upload_dir'] . $nome_diretorio;
        $this->criarDiretorio($diretorio_projeto);
        
        return $this->processarArquivoIndividual($arquivo, $diretorio_projeto, $nome_diretorio, $projeto_id, $conn);
    }
    
    /**
     * Processa um arquivo individual
     */
    private function processarArquivoIndividual($arquivo, $diretorio_projeto, $nome_diretorio, $projeto_id, $conn) {
        // Gerar nome único para o arquivo
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $nome_limpo = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($arquivo['name'], PATHINFO_FILENAME));
        $nome_unico = uniqid() . "_" . time() . "_" . $nome_limpo . "." . $extensao;
        $caminho_completo = $diretorio_projeto . "/" . $nome_unico;
        $caminho_relativo = "uploads/" . $nome_diretorio . "/" . $nome_unico;
        
        // Mover arquivo
        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
            return [
                'sucesso' => false,
                'erro' => 'Erro ao mover arquivo.',
                'arquivo_info' => null
            ];
        }
        
        $arquivo_info = [
            'nome_original' => $arquivo['name'],
            'nome_unico' => $nome_unico,
            'caminho_completo' => $caminho_completo,
            'caminho_relativo' => $caminho_relativo,
            'tamanho' => $arquivo['size'],
            'tipo_mime' => mime_content_type($caminho_completo)
        ];
        
        // Salvar no banco se conexão fornecida
        if ($conn && $projeto_id) {
            $insert = $conn->prepare("INSERT INTO uploads (nome_arquivo, caminho_arquivo, projeto_id, tamanho_arquivo, tipo_mime) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssis", 
                $arquivo_info['nome_original'], 
                $arquivo_info['caminho_relativo'], 
                $projeto_id, 
                $arquivo_info['tamanho'], 
                $arquivo_info['tipo_mime']
            );
            
            if (!$insert->execute()) {
                // Remove arquivo se não conseguiu salvar no banco
                unlink($caminho_completo);
                $insert->close();
                return [
                    'sucesso' => false,
                    'erro' => 'Erro ao salvar no banco de dados.',
                    'arquivo_info' => null
                ];
            }
            $arquivo_info['upload_id'] = $insert->insert_id;
            $insert->close();
        }
        
        return [
            'sucesso' => true,
            'erro' => null,
            'arquivo_info' => $arquivo_info
        ];
    }
    
    /**
     * Valida um arquivo antes do upload
     */
    private function validarArquivo($arquivo) {
        $erros = [];
        
        // Verificar se houve erro no upload
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            switch ($arquivo['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $erros[] = "Arquivo muito grande.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $erros[] = "Upload incompleto.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $erros[] = "Nenhum arquivo enviado.";
                    break;
                default:
                    $erros[] = "Erro no upload.";
            }
            return $erros;
        }
        
        // Verificar tamanho
        if ($arquivo['size'] > $this->config['max_file_size']) {
            $erros[] = "Arquivo excede o tamanho máximo de " . ($this->config['max_file_size'] / 1024 / 1024) . "MB.";
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->config['allowed_types'])) {
            $erros[] = "Tipo de arquivo não permitido.";
        }
        
        // Verificar extensão
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, $this->config['allowed_extensions'])) {
            $erros[] = "Extensão de arquivo não permitida.";
        }
        
        return $erros;
    }
    
    /**
     * Cria diretório se não existir
     */
    private function criarDiretorio($caminho) {
        if (!is_dir($caminho)) {
            mkdir($caminho, 0755, true);
        }
    }
    
    /**
     * Sanitiza nome de diretório
     */
    private function sanitizarNomeDiretorio($nome) {
        // Remove caracteres especiais e mantém apenas letras, números, espaços, hífens e underscores
        $nome_limpo = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $nome);
        // Substitui espaços múltiplos por um único espaço
        $nome_limpo = preg_replace('/\s+/', ' ', $nome_limpo);
        // Substitui espaços por underscores
        $nome_limpo = str_replace(' ', '_', $nome_limpo);
        // Remove underscores múltiplos
        $nome_limpo = preg_replace('/_+/', '_', $nome_limpo);
        // Remove underscores no início e fim
        $nome_limpo = trim($nome_limpo, '_');
        // Limita o tamanho do nome (opcional)
        $nome_limpo = substr($nome_limpo, 0, 50);
        
        return $nome_limpo;
    }
    
    /**
     * Gera mensagem de resultado do processamento
     */
    private function gerarMensagemResultado($arquivos_processados, $erros_upload) {
        $mensagem = '';
        
        if ($arquivos_processados > 0) {
            $mensagem = "$arquivos_processados arquivo(s) enviado(s) com sucesso.";
        }
        
        if (!empty($erros_upload)) {
            if (!empty($mensagem)) $mensagem .= " ";
            $mensagem .= "Alguns arquivos não puderam ser enviados.";
        }
        
        return $mensagem;
    }
    
    /**
     * Remove arquivo do sistema e banco de dados
     */
    public function removerArquivo($arquivo_id, $conn) {
        $stmt = $conn->prepare("SELECT caminho_arquivo FROM uploads WHERE id = ?");
        $stmt->bind_param("i", $arquivo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $caminho_completo = "../" . $row['caminho_arquivo'];
            
            // Remove arquivo físico
            if (file_exists($caminho_completo)) {
                unlink($caminho_completo);
            }
            
            // Remove do banco
            $delete = $conn->prepare("DELETE FROM uploads WHERE id = ?");
            $delete->bind_param("i", $arquivo_id);
            $sucesso = $delete->execute();
            $delete->close();
            
            $stmt->close();
            return $sucesso;
        }
        
        $stmt->close();
        return false;
    }
}
?>