import { lstatSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

// Gitleaks also loads this file from the source, regardless of --gitleaks-ignore-path.
export function assertNoSourceIgnores(sources) {
  if (sources.length === 0) throw new Error('At least one scan source is required.');
  for (const source of sources) {
    try {
      lstatSync(resolve(source, '.gitleaksignore'));
    } catch (error) {
      if (error.code === 'ENOENT') continue;
      throw new Error('Cannot verify secret scan source.', { cause: error });
    }
    throw new Error('Secret scanning blocked: source contains .gitleaksignore.');
  }
}

if (process.argv[1] && pathToFileURL(resolve(process.argv[1])).href === import.meta.url) {
  try {
    assertNoSourceIgnores(process.argv.slice(2));
  } catch (error) {
    process.stderr.write(error.message + '\n');
    process.exitCode = 2;
  }
}
