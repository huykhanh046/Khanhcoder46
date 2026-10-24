import { existsSync, writeFileSync } from 'node:fs';
import { randomBytes } from 'node:crypto';
import { execFileSync } from 'node:child_process';
if (!existsSync('.env')) {
  writeFileSync(
    '.env',
    `DATABASE_URL=postgresql://khfashion:khfashion_dev@localhost:5432/khfashion?schema=public\nSESSION_SECRET=${randomBytes(48).toString('hex')}\nAPP_URL=http://localhost:3000\nCOOKIE_SECURE=false\n`,
    { mode: 0o600 },
  );
  console.log('Created local .env with random session key.');
}
for (const args of [
  ['prisma', 'generate'],
  ['tsx', 'scripts/migrate.ts'],
  ['tsx', 'prisma/seed.ts'],
])
  execFileSync('npx', args, { stdio: 'inherit' });
