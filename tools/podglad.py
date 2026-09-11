import re,sys,urllib.request,pathlib
base='http://przedszkole.ddev.site'
path=sys.argv[1] if len(sys.argv)>1 else '/'
out=sys.argv[2] if len(sys.argv)>2 else 'podglad.html'
html=urllib.request.urlopen(base+path).read().decode('utf-8')
def grab(u):
    if u.startswith('//'): u='http:'+u
    if not u.startswith('http'): u=base+u
    return urllib.request.urlopen(u).read().decode('utf-8')
# wklejenie wszystkich lokalnych arkuszy
for m in list(re.finditer(r"<link[^>]+rel=['\"]stylesheet['\"][^>]*>", html)):
    tag=m.group(0)
    href=re.search(r"href=['\"]([^'\"]+)['\"]", tag)
    if href and 'przedszkole.ddev.site' in href.group(1):
        try:
            css=grab(href.group(1))
            # Po wklejeniu CSS do HTML wzgledne url() przestaja dzialac -
            # zamieniamy fonty na data URI, reszte na adresy bezwzgledne.
            import base64 as _b64, urllib.parse as _up
            bazowy=_up.urljoin(base, href.group(1))
            def _url(m):
                u=m.group(1).strip('\'"')
                if u.startswith(('data:','http')): return m.group(0)
                pelny=_up.urljoin(bazowy,u)
                if u.endswith('.woff2'):
                    try:
                        raw=urllib.request.urlopen(pelny).read()
                        return 'url("data:font/woff2;base64,%s")' % _b64.b64encode(raw).decode()
                    except Exception: return m.group(0)
                return 'url("%s")' % pelny
            css=re.sub(r'url\(([^)]+)\)', _url, css)
            html=html.replace(tag, "<style>\n"+css+"\n</style>")
        except Exception: pass
for m in list(re.finditer(r"<script[^>]+src=['\"]([^'\"]+)['\"][^>]*></script>", html)):
    if 'przedszkole.ddev.site' in m.group(1):
        try: html=html.replace(m.group(0), "<script>\n"+grab(m.group(1))+"\n</script>")
        except Exception: pass
# obrazki jako data URI (panel podgladu blokuje pliki podrzedne)
import base64, mimetypes
def osadz(m):
    u=m.group(1)
    full = u if u.startswith('http') else base+u
    if 'przedszkole.ddev.site' not in full: return m.group(0)
    try:
        raw=urllib.request.urlopen(full).read()
        mime=mimetypes.guess_type(full.split('?')[0])[0] or 'image/png'
        return 'src="data:%s;base64,%s"' % (mime, base64.b64encode(raw).decode())
    except Exception:
        return m.group(0)
html=re.sub(r'src="([^"]+\.(?:svg|png|jpe?g|webp|gif)[^"]*)"', osadz, html)
html=re.sub(r"srcset=\"[^\"]*\"", "", html)
pathlib.Path(out).write_text(html,encoding='utf-8')
print("OK:",out,len(html),"bajtow")
