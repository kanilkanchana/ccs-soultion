"""Guard against an inline @php being consumed by a later @endphp block.
This checks raw block boundaries, not PHP execution or full Blade compilation.
"""
from pathlib import Path
import re
source = (Path(__file__).resolve().parents[2] / "resources/views/ccs/invoice.blade.php").read_text()
blocks = re.findall(r"(?<!@)@php(.*?)@endphp", source, re.S)
assert blocks, "Expected explicit PHP blocks"
for block in blocks:
    assert not re.search(r"@(if|endif|foreach|endforeach|php)\b", block), "Blade control directive swallowed into a raw PHP block"
assert "@php(" not in source, "Use explicit PHP blocks consistently in this invoice view"
print("PASS: invoice PHP blocks cannot swallow print settings, draft serial conditions or receipt loops.")
