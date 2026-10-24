import { pageUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { VoucherForm } from '@/components/admin-forms';
export default async function Page() {
  await pageUser(true);
  const vouchers = await db.voucher.findMany({ orderBy: { code: 'asc' } });
  return (
    <>
      <section className="panel">
        <h2>Tạo mã ưu đãi</h2>
        <VoucherForm />
      </section>
      <section className="panel">
        <h2>Voucher hiện có</h2>
        {vouchers.map((v) => (
          <details key={v.id} style={{ padding: '15px 0', borderTop: '1px solid var(--line)' }}>
            <summary style={{ cursor: 'pointer', marginBottom: 15 }}>
              {v.code} · Giảm {v.percent}% · Đã dùng {v.used}/{v.usageLimit} ·{' '}
              {v.active ? 'Đang bật' : 'Đang tắt'}
            </summary>
            <VoucherForm voucher={v} />
          </details>
        ))}
      </section>
    </>
  );
}
