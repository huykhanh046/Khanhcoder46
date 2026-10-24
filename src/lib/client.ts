export async function api<T = Record<string, unknown>>(
  path: string,
  data: unknown = {},
  method = 'POST',
): Promise<T> {
  const response = await fetch(`/api/${path}`, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.error || 'Không thể thực hiện thao tác');
  return result as T;
}
