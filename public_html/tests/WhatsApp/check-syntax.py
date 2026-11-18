"""Optional static PHP parser check; not a substitute for PHP/PHPUnit runtime tests."""
from pathlib import Path
import tree_sitter_php
from tree_sitter import Language, Parser

root = Path(__file__).resolve().parents[2]
parser = Parser(Language(tree_sitter_php.language_php()))
files = [root / 'config/ccs_whatsapp_test.php', root / 'app/Services/CcsWhatsAppTest.php',
         root / 'app/Console/Commands/TestCcsWhatsApp.php', Path(__file__).with_name('CcsWhatsAppTestTest.php')]
for file in files:
    tree = parser.parse(file.read_bytes())
    if tree.root_node.has_error:
        raise SystemExit(f'PHP parse error: {file.relative_to(root)}')
    print(f'PASS: {file.relative_to(root)}')
