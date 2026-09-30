#!/usr/bin/env bash
# Genera un ZIP listo para subir a un hosting compartido (cPanel, HostGator…):
# incluye vendor/ de producción y excluye config.php, logs, uploads y .git.
# Uso: bin/empaquetar.sh [salida.zip]
set -euo pipefail
cd "$(dirname "$0")/.."
OUT="${1:-tienda-$(date +%Y%m%d).zip}"
[[ "$OUT" = /* ]] || OUT="$PWD/$OUT"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

git ls-files -z --cached --others --exclude-standard | grep -zv '^bin/empaquetar.sh$' | xargs -0 -I{} cp --parents {} "$TMP/"
cp composer.json composer.lock "$TMP/"
composer install -q -d "$TMP" --no-dev --prefer-dist --optimize-autoloader --no-interaction
rm -f "$TMP/config.php"
mkdir -p "$TMP/storage/mails" "$TMP/storage/logs" "$TMP/storage/sessions" "$TMP/uploads"

rm -f "$OUT"
(cd "$TMP" && zip -qr -X "$OUT" . -x '*/.git/*' '.git/*' 'vendor/*/tests/*' 'vendor/*/*/tests/*' 'vendor/*/*/examples/*')
echo "Creado $OUT ($(du -h "$OUT" | cut -f1))"
