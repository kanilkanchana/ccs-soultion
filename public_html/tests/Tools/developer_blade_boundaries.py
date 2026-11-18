"""Check the Blade statement boundary that caused the Team/Calendar failures.

This is a source regression check, not PHP interpreter validation. Laravel's
statement scanner uses a non-word boundary before @; consecutive directives
such as @endif@endforeach leave the second directive uncompiled.
"""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
STATEMENT = re.compile(r'\B@(@?\w+(?:::\w+)?)')

def check(source):
    stack = []
    for match in STATEMENT.finditer(source):
        directive = match.group(1)
        if directive in ('if', 'foreach', 'forelse'):
            stack.append(directive)
        elif directive in ('endif', 'endforeach', 'endforelse'):
            expected = {'endif': 'if', 'endforeach': 'foreach', 'endforelse': 'forelse'}[directive]
            if not stack or stack.pop() != expected:
                raise AssertionError('Unmatched compiled directive: ' + directive)
    assert not stack, 'Unclosed compiled directives: ' + str(stack)

# Prove the reported former syntax is rejected, even though raw tokens balance.
for source in ('@foreach($a as $b)@if($b)<p>text</p>@endif@endforeach',
               '@if($ok)@foreach($a as $b)@if($b)<p>text</p>@endif@endforeach\n@endif'):
    try:
        check(source)
    except AssertionError:
        pass
    else:
        raise AssertionError('The regression sample was not detected')
    check(source.replace('@endif@endforeach', '@endif\n@endforeach'))

for file in (ROOT / 'resources/views/developer').rglob('*.blade.php'):
    source = file.read_text()
    assert not re.search(r'@(endif|endforeach|endforelse)@', source), file
    try:
        check(source)
    except AssertionError as error:
        raise AssertionError(str(file) + ': ' + str(error)) from error
print('PASS: old Team/Calendar directive-boundary failures reproduced; all developer views pass fixed boundaries')
