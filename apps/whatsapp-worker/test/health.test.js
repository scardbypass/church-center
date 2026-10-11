import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

test('worker exposes a health route and does not claim WhatsApp is connected', () => {
  const code = readFileSync(new URL('../chiwa.js', import.meta.url), 'utf8');
  assert.match(code, /request\.url !== '\/health'/);
  assert.match(code, /whatsapp: 'not_configured'/);
  assert.match(code, /server\.close/);
});
