import 'dotenv/config';
import { expireOrders } from '../src/lib/orders';
import { db } from '../src/lib/db';
async function run() {
  try {
    await expireOrders();
  } catch (e) {
    console.error('Expiration job failed', e instanceof Error ? e.message : 'unknown');
  }
}
await run();
if (process.argv.includes('--watch')) {
  setInterval(run, 60_000);
} else {
  await db.$disconnect();
}
