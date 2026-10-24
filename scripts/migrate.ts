import 'dotenv/config';
import { Client } from 'pg';
import { readdir, readFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
const db = new Client({ connectionString: process.env.DATABASE_URL });
await db.connect();
try {
  await db.query('BEGIN');
  await db.query('SELECT pg_advisory_xact_lock(46102026)');
  await db.query(
    'CREATE TABLE IF NOT EXISTS "_kh_migrations" (name TEXT PRIMARY KEY, checksum TEXT NOT NULL, applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW())',
  );
  const folders = (await readdir('prisma/migrations', { withFileTypes: true }))
    .filter((f) => f.isDirectory())
    .map((f) => f.name)
    .sort();
  for (const name of folders) {
    const sql = await readFile(`prisma/migrations/${name}/migration.sql`, 'utf8');
    const checksum = createHash('sha256').update(sql).digest('hex');
    const existing = await db.query<{ checksum: string }>(
      'SELECT checksum FROM "_kh_migrations" WHERE name=$1',
      [name],
    );
    if (existing.rows[0]) {
      if (existing.rows[0].checksum !== checksum)
        throw new Error(`Migration ${name} changed after application`);
      console.log(`Already applied: ${name}`);
      continue;
    }
    if (!sql.trim()) throw new Error(`Empty migration ${name}`);
    await db.query(sql);
    await db.query('INSERT INTO "_kh_migrations" (name,checksum) VALUES ($1,$2)', [name, checksum]);
    console.log(`Applied: ${name}`);
  }
  await db.query('COMMIT');
} catch (e) {
  await db.query('ROLLBACK');
  throw e;
} finally {
  await db.end();
}
