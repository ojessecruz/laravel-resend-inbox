<?php

declare(strict_types=1);

return [
    'titles' => [
        'inbox' => 'Caixa de entrada',
        'compose' => 'Novo e-mail',
        'reply' => 'Responder',
    ],
    'tabs' => [
        'label' => 'Caixas',
        'all' => 'Todas',
        'others' => 'Outros',
    ],
    'status' => [
        'inbox' => 'Caixa de entrada',
        'unread' => 'Não lidas',
        'archived' => 'Arquivadas',
    ],
    'list' => [
        'search' => 'Buscar por assunto ou remetente',
        'status' => 'Situação',
        'empty' => 'Nenhuma conversa aqui.',
        'to' => 'Para: :address',
        'selected' => ':count selecionada|:count selecionadas',
    ],
    'actions' => [
        'compose' => 'Novo e-mail',
        'send' => 'Enviar',
        'send_reply' => 'Enviar resposta',
        'archive' => 'Arquivar',
        'unarchive' => 'Mover para a caixa de entrada',
        'mark_read' => 'Marcar como lida',
        'mark_unread' => 'Marcar como não lida',
        'load_images' => 'Carregar imagens',
        'select' => 'Selecionar conversa',
        'select_page' => 'Selecionar todas as conversas desta página',
        'clear_selection' => 'Limpar seleção',
    ],
    'notices' => [
        'archived' => ':count conversa arquivada.|:count conversas arquivadas.',
        'unarchived' => ':count conversa voltou para a caixa de entrada.|:count conversas voltaram para a caixa de entrada.',
        'marked_read' => ':count conversa marcada como lida.|:count conversas marcadas como lidas.',
        'reply_sent' => 'Resposta enviada.',
    ],
    'message' => [
        'to' => 'Para: :address',
        'cc' => 'Cc: :address',
        'sent_by' => 'Enviado por :name',
        'body' => 'Corpo do e-mail',
        'attachment' => 'Anexo',
        'images_blocked' => 'As imagens externas estão bloqueadas.',
    ],
    'fields' => [
        'from' => 'De',
        'to' => 'Para',
        'to_hint' => 'Separe vários endereços com vírgula.',
        'subject' => 'Assunto',
        'body' => 'Mensagem',
        'body_hint' => 'Aceita Markdown.',
    ],
    'validation' => [
        'sender' => 'Escolha um dos endereços de envio permitidos.',
        'recipients' => 'Adicione pelo menos um destinatário.',
        'recipient' => ':address não é um endereço de e-mail válido.',
        'send_failed' => 'O Resend não conseguiu enviar o e-mail. Tente de novo em instantes.',
    ],
    'delivery' => [
        'sent' => 'Enviado',
        'delivered' => 'Entregue',
        'bounced' => 'Devolvido',
        'complained' => 'Marcado como spam',
    ],
    'no_subject' => '(sem assunto)',
    'attachment_unavailable' => 'Não foi possível buscar o anexo no Resend.',
];
