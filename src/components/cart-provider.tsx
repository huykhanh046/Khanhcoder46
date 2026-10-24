'use client';
import { createContext, useContext, useEffect, useState, useCallback, type ReactNode } from 'react';
export type CartItem = {
  variantId: string;
  name: string;
  slug: string;
  size: string;
  color: string;
  price: number;
  image: string;
  quantity: number;
  stock: number;
};
type Cart = {
  items: CartItem[];
  ready: boolean;
  add: (item: CartItem) => void;
  update: (id: string, q: number) => void;
  remove: (id: string) => void;
  clear: () => void;
  notice: string;
  setNotice: (s: string) => void;
};
const Context = createContext<Cart | null>(null);
export function CartProvider({ children }: { children: ReactNode }) {
  const [items, setItems] = useState<CartItem[]>([]),
    [ready, setReady] = useState(false),
    [notice, setNotice] = useState('');
  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      try {
        const data = JSON.parse(localStorage.getItem('kh-cart') || '[]');
        if (Array.isArray(data))
          setItems(
            data
              .filter(
                (v) =>
                  typeof v.variantId === 'string' &&
                  Number.isInteger(v.quantity) &&
                  v.quantity > 0 &&
                  Number.isFinite(v.price) &&
                  typeof v.name === 'string' &&
                  typeof v.image === 'string' &&
                  v.image.startsWith('/images/'),
              )
              .slice(0, 50),
          );
      } catch {}
      setReady(true);
    });
    return () => cancelAnimationFrame(frame);
  }, []);
  useEffect(() => {
    if (ready) {
      try {
        localStorage.setItem('kh-cart', JSON.stringify(items));
      } catch {}
    }
  }, [items, ready]);
  useEffect(() => {
    if (!notice) return;
    const timer = setTimeout(() => setNotice(''), 5000);
    return () => clearTimeout(timer);
  }, [notice]);
  const add = useCallback((item: CartItem) => {
    setItems((old) => {
      const found = old.find((i) => i.variantId === item.variantId);
      return found
        ? old.map((i) =>
            i.variantId === item.variantId
              ? { ...item, quantity: Math.min(found.quantity + item.quantity, item.stock, 20) }
              : i,
          )
        : [...old, item];
    });
    setNotice('Đã thêm sản phẩm vào giỏ hàng');
  }, []);
  const remove = (id: string) => setItems((old) => old.filter((i) => i.variantId !== id));
  return (
    <Context.Provider
      value={{
        items,
        ready,
        add,
        remove,
        update: (id, q) =>
          setItems((old) =>
            old.map((i) =>
              i.variantId === id
                ? { ...i, quantity: Math.max(1, Math.min(q, 20, i.stock || 20)) }
                : i,
            ),
          ),
        clear: () => setItems([]),
        notice,
        setNotice,
      }}
    >
      {children}
      {notice && (
        <div className="toast" role="status">
          {notice}
          <button aria-label="Đóng thông báo" onClick={() => setNotice('')}>
            ×
          </button>
        </div>
      )}
    </Context.Provider>
  );
}
export function useCart() {
  const value = useContext(Context);
  if (!value) throw new Error('CartProvider missing');
  return value;
}
