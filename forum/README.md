# Fórum Escolar (estilo Reddit + Brainly)

Aplicação básica em HTML, CSS, JS, PHP e MySQL.

## Como rodar

1. Instale o XAMPP (ou outro servidor com PHP + MySQL) e inicie Apache e MySQL.
2. Copie esta pasta para `htdocs` (ex.: `htdocs/forum`).
3. Importe o banco: no phpMyAdmin, aba "Importar", escolha `schema.sql`
   (ou rode `mysql -u root < schema.sql`).
4. Se seu MySQL tiver senha, ajuste usuário/senha no `config.php`.
5. Abra `http://localhost/forum/`.

## Tipos de usuário

| Tipo | O que pode |
|---|---|
| Visitante (sem login) | Ver tudo. Não pergunta, não responde, não vota. |
| Normal | Navegar, criar matérias, perguntar, responder, votar em enquetes. |
| Especialista | Igual ao normal, mas suas respostas têm destaque (selo, cor e ficam no topo). |
| Moderador | Tudo do normal + criar/encerrar enquetes, banir/desbanir, definir especialistas, apagar matérias, perguntas e respostas. |

**O primeiro usuário cadastrado vira moderador automaticamente.**
Depois, no "Painel", o moderador pode transformar outros usuários em especialistas ou moderadores.

## Enquetes

O moderador cria uma enquete com o nome de uma matéria nova. Usuários votam Sim/Não.
Ao encerrar, se "Sim" tiver mais votos, a matéria é criada.

## Arquivos

- `schema.sql`: tabelas do banco
- `config.php`: conexão e funções de permissão
- `header.php` / `footer.php`: layout comum
- `index.php`: matérias · `categoria.php`: perguntas · `pergunta.php`: respostas
- `enquetes.php` · `admin.php` · `login.php` · `registro.php` · `logout.php`
- `style.css` · `script.js`

## Observação

É um código didático. Para uso real, acrescente proteção CSRF, limite de tentativas de login e HTTPS.
