import { pageUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { AddressForm, ActionButton } from '@/components/account';
export default async function Page() {
  const user = await pageUser();
  const addresses = await db.address.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: 'desc' },
  });
  return (
    <>
      <section className="panel">
        <h2>Sổ địa chỉ</h2>
        {!addresses.length && <p className="muted">Bạn chưa lưu địa chỉ nào.</p>}
        {addresses.map((a) => (
          <div className="address-card" key={a.id}>
            <strong>{a.name}</strong>
            <p>{a.phone}</p>
            <p>{a.detail}</p>
            <details style={{ margin: '12px 0' }}>
              <summary style={{ cursor: 'pointer', marginBottom: 12 }}>Sửa địa chỉ</summary>
              <AddressForm address={a} />
            </details>
            <ActionButton
              path={`account/addresses/${a.id}`}
              method="DELETE"
              label="Xóa địa chỉ"
              confirmMessage="Xóa địa chỉ này?"
            />
          </div>
        ))}
      </section>
      <section className="panel">
        <h2>Thêm địa chỉ mới</h2>
        <AddressForm />
      </section>
    </>
  );
}
