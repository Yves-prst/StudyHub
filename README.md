# Fórum Escolar (estilo Reddit + Brainly)

Aplicação básica em HTML, CSS, JS, PHP e MySQL.

## Como rodar

1. Instale o XAMPP (ou outro servidor com PHP + MySQL) e inicie Apache e MySQL.
2. Copie esta pasta para `htdocs` (ex.: `htdocs/forum`).
3. Importe o banco: no phpMyAdmin, aba "Importar", escolha `schema.sql`
   (ou rode `mysql -u root < schema.sql`).
4. Se seu MySQL tiver senha, ajuste usuário/senha no `config.php`.
5. Abra `http://localhost/forum/`.

> **Já tinha importado uma versão anterior?** Não precisa apagar nada, importe só a migração:
> - Versão com perfil e avaliações (sem foto): `migracao_2_para_3.sql`
> - Primeira versão do fórum: `migracao_1_para_2.sql` e depois `migracao_2_para_3.sql`

> **Foto de perfil:** as imagens ficam em `uploads/fotos/`. Em Linux/Mac, se o envio falhar por
> permissão, rode `chmod 775 uploads/fotos` (no XAMPP/Windows normalmente funciona direto).

## Tipos de usuário

| Tipo | O que pode |
|---|---|
| Visitante (sem login) | Ver tudo. Não pergunta, não responde, não avalia, não vota. |
| Normal | Navegar, perguntar, responder, responder respostas, avaliar, votar em enquetes, editar o perfil e a foto. |
| Especialista | Igual ao normal, mas suas respostas têm destaque (selo, cor e ficam no topo). |
| Moderador | Tudo do normal + criar matérias, criar/encerrar enquetes, banir/desbanir, mudar o tipo dos usuários, apagar matérias, perguntas, respostas, comentários e fotos inapropriadas. |

- **Todo usuário novo começa como normal.** Só o moderador muda o tipo, no "Painel".
- **O primeiro usuário cadastrado vira moderador** (para o sistema ter alguém que gerencie). Todos os seguintes são normais.

## Perfil e foto

`perfil.php` mostra foto, nome, tipo, descrição, quantidade de perguntas/respostas, nota média
recebida e as últimas perguntas e respostas. Cada nome no site leva ao perfil da pessoa.
No próprio perfil dá para trocar a foto (JPG, PNG, GIF ou WEBP até 2 MB), remover a foto e
editar nome e "sobre mim". Quem não tem foto aparece com um círculo com a inicial do nome.
O moderador pode remover a foto de outro usuário.

## Respostas às respostas

Dentro de cada resposta existe uma área "💬 respostas a esta resposta": usuários logados escrevem
um comentário curto (até 500 caracteres) respondendo aquela resposta. O moderador pode apagar
comentários inapropriados.

## Avaliação de respostas

Usuários logados dão nota de 1 a 5 estrelas nas respostas dos outros (uma nota por resposta,
pode mudar depois; não dá para avaliar a própria resposta). Dentro de cada grupo, as respostas
mais bem avaliadas aparecem primeiro, com as de especialistas sempre no topo.

## Matérias e enquetes

- **Só o moderador cria matérias** diretamente (página inicial).
- O moderador também pode criar uma **enquete** com o nome de uma matéria nova. Usuários votam Sim/Não.
  Ao clicar em "Encerrar e aplicar resultado": se "Sim" tiver mais votos, a matéria é **criada
  automaticamente** e a enquete mostra "Aprovada" com link para ela; em caso de empate ou "Não"
  vencendo, aparece "Rejeitada".

## Arquivos

- `schema.sql`: tabelas do banco · `migracao_*.sql`: atualização de quem já tinha o banco antigo
- `config.php`: conexão, permissões, estrelas e foto de perfil (função `avatar`)
- `header.php` / `footer.php`: layout comum
- `index.php`: matérias · `categoria.php`: perguntas · `pergunta.php`: respostas, avaliação e comentários
- `perfil.php` · `enquetes.php` · `admin.php` · `login.php` · `registro.php` · `logout.php`
- `uploads/fotos/`: fotos de perfil (o `.htaccess` da pasta `uploads` bloqueia execução de scripts)
- `style.css` · `script.js`

## Observação

É um código didático. Para uso real, acrescente proteção CSRF, limite de tentativas de login e HTTPS.
