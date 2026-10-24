'use client';
import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { useCart } from './cart-provider';
import { api } from '@/lib/client';
import { money } from '@/lib/utils';
import { Button } from './ui/button';
type Address = { id: string; name: string; phone: string; detail: string };
type Totals = { subtotal: number; discount: number; shipping: number; total: number };
export function Checkout({ addresses, vnpay }: { addresses: Address[]; vnpay: boolean }) {
  const { items, ready, clear } = useCart(),
    router = useRouter();
  const [selected, setSelected] = useState(addresses[0]?.id || ''),
    [voucher, setVoucher] = useState(''),
    [applied, setApplied] = useState(''),
    [totals, setTotals] = useState<Totals | null>(null),
    [error, setError] = useState(''),
    [busy, setBusy] = useState(false),
    [quoting, setQuoting] = useState(false);
  const lines = items.map((i) => ({ variantId: i.variantId, quantity: i.quantity }));
  const fingerprint = JSON.stringify(lines);
  useEffect(() => {
    if (!ready || !items.length) return;
    let alive = true;
    api<Totals>('orders/quote', { items: JSON.parse(fingerprint), voucher: '' })
      .then((r) => {
        if (alive) {
          setTotals(r);
          setError('');
        }
      })
      .catch((e) => {
        if (alive) setError(e.message);
      });
    return () => {
      alive = false;
    };
  }, [ready, fingerprint, items.length]);
  async function apply() {
    setQuoting(true);
    setError('');
    try {
      const r = await api<Totals>('orders/quote', { items: lines, voucher });
      setTotals(r);
      setApplied(voucher.toUpperCase());
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setQuoting(false);
    }
  }
  async function submit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setBusy(true);
    setError('');
    const f = Object.fromEntries(new FormData(e.currentTarget));
    try {
      let key = sessionStorage.getItem('kh-checkout-key');
      if (!key) {
        key = crypto.randomUUID();
        sessionStorage.setItem('kh-checkout-key', key);
      }
      const order = await api<{ id: string; paymentUrl: string | null }>('orders', {
        ...f,
        items: lines,
        voucher: applied,
        expectedTotal: totals?.total,
        idempotencyKey: key,
      });
      clear();
      sessionStorage.removeItem('kh-checkout-key');
      if (order.paymentUrl) location.assign(order.paymentUrl);
      else router.push(`/tai-khoan/don-hang/${order.id}?created=1`);
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusy(false);
    }
  }
  const address = addresses.find((a) => a.id === selected);
  if (!ready) return <div className="skeleton" />;
  if (!items.length)
    return (
      <div className="container section empty">
        <h1 style={{ fontSize: 30 }}>Chưa có sản phẩm để đặt hàng</h1>
        <Button asChild>
          <Link href="/san-pham">Khám phá sản phẩm</Link>
        </Button>
      </div>
    );
  return (
    <div className="container">
      <div className="breadcrumb">
        <Link href="/gio-hang">Giỏ hàng</Link> / Thanh toán
      </div>
      <div className="page-heading">
        <span className="eyebrow">ONE STEP CLOSER</span>
        <h1>Hoàn tất đơn hàng</h1>
        <p>Kiểm tra thông tin để những món đồ đến đúng nơi bạn muốn.</p>
      </div>
      <div className="checkout-grid">
        <form className="form-stack" onSubmit={submit} id="checkout-form">
          <h2 style={{ fontSize: 22, margin: 0 }}>Thông tin giao hàng</h2>
          {addresses.length > 0 && (
            <label>
              Chọn địa chỉ đã lưu
              <select value={selected} onChange={(e) => setSelected(e.target.value)}>
                <option value="">Nhập địa chỉ khác</option>
                {addresses.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.name} — {a.detail}
                  </option>
                ))}
              </select>
            </label>
          )}
          <div className="form-stack" key={selected}>
            <div className="two-cols">
              <label>
                Họ tên người nhận
                <input
                  name="recipient"
                  defaultValue={address?.name}
                  required
                  minLength={2}
                  maxLength={100}
                  autoComplete="shipping name"
                />
              </label>
              <label>
                Số điện thoại
                <input
                  name="phone"
                  defaultValue={address?.phone}
                  type="tel"
                  required
                  pattern="(0|\+84)[0-9]{9,10}"
                  autoComplete="shipping tel"
                />
              </label>
            </div>
            <label>
              Địa chỉ đầy đủ
              <input
                name="address"
                defaultValue={address?.detail}
                required
                minLength={10}
                maxLength={500}
                placeholder="Số nhà, đường, phường/xã, tỉnh/thành phố"
                autoComplete="shipping street-address"
              />
            </label>
          </div>
          <label>
            Ghi chú đơn hàng
            <textarea
              name="note"
              maxLength={500}
              placeholder="Lưu ý dành cho người giao hàng (nếu có)"
            />
          </label>
          <div className="divider" />
          <h2 style={{ fontSize: 22, margin: 0 }}>Phương thức thanh toán</h2>
          <label className="check-label">
            <input name="paymentMethod" type="radio" value="COD" defaultChecked /> Thanh toán khi
            nhận hàng (COD)
          </label>
          {vnpay ? (
            <label className="check-label">
              <input name="paymentMethod" type="radio" value="VNPAY" /> VNPay sandbox — giao dịch
              thử nghiệm
            </label>
          ) : (
            <p className="muted" style={{ fontSize: 12 }}>
              Thanh toán trực tuyến hiện chưa được bật.
            </p>
          )}
          {error && (
            <div className="alert" role="alert">
              {error}
            </div>
          )}
        </form>
        <aside className="summary">
          <h2>Đơn hàng của bạn</h2>
          {items.map((i) => (
            <div key={i.variantId} className="summary-row">
              <span>
                {i.name} × {i.quantity}
                <br />
                <small>
                  {i.color} / {i.size}
                </small>
              </span>
              <span>{money(i.price * i.quantity)}</span>
            </div>
          ))}
          <div className="divider" />
          <label>
            Mã ưu đãi
            <div style={{ display: 'flex', gap: 8 }}>
              <input
                aria-label="Mã ưu đãi"
                value={voucher}
                maxLength={30}
                onChange={(e) => setVoucher(e.target.value)}
                placeholder="Ví dụ: WELCOME10"
              />
              <Button
                type="button"
                variant="outline"
                style={{ marginTop: 0, padding: '8px 12px' }}
                disabled={quoting || busy}
                onClick={apply}
              >
                {quoting ? '...' : 'Áp dụng'}
              </Button>
            </div>
          </label>
          {applied && (
            <p className="stock" style={{ marginTop: 10 }}>
              Đã áp dụng {applied}
            </p>
          )}
          {totals && (
            <>
              <div className="summary-row">
                <span>Tạm tính</span>
                <span>{money(totals.subtotal)}</span>
              </div>
              <div className="summary-row">
                <span>Giảm giá</span>
                <span>−{money(totals.discount)}</span>
              </div>
              <div className="summary-row">
                <span>Vận chuyển</span>
                <span>{totals.shipping ? money(totals.shipping) : 'Miễn phí'}</span>
              </div>
              <div className="summary-row total">
                <span>Tổng thanh toán</span>
                <span>{money(totals.total)}</span>
              </div>
            </>
          )}
          <Button
            type="submit"
            form="checkout-form"
            className="full"
            disabled={busy || !totals || quoting}
          >
            {busy ? 'Đang tạo đơn...' : 'Xác nhận đặt hàng'}
          </Button>
          <p style={{ fontSize: 11, marginTop: 15 }} className="muted">
            Bằng việc đặt hàng, bạn đồng ý với{' '}
            <Link href="/chinh-sach" style={{ textDecoration: 'underline' }}>
              chính sách mua hàng
            </Link>
            . Tổng tiền cuối cùng được xác nhận tại server.
          </p>
        </aside>
      </div>
    </div>
  );
}
