from pathlib import Path
out=Path('public/images')
colors=['#e8dfd0','#c5c4ad','#343d36','#c9a18a','#9caab6']
for g in range(4):
 for i in range(5):
  n=g*5+i+1;c=colors[i]
  if g==0:
   shape=f'<path d="M180 180 260 145 Q300 185 340 145 L420 180 470 280 400 312 374 265 380 650 220 650 226 265 200 312 130 280Z" fill="{c}"/><path d="M260 145Q300 220 340 145" fill="none" stroke="#ffffff88" stroke-width="12"/><path d="M300 240V610" stroke="#00000009" stroke-width="3"/>'
  elif g==1:
   shape=f'<path d="M210 165H390L410 670H322L300 350 278 670H190Z" fill="{c}"/><path d="M210 193H390M300 195V350M224 205 220 255M376 205 380 255" stroke="#00000025" stroke-width="3" fill="none"/><circle cx="300" cy="180" r="4" fill="#847565"/>'
  elif g==2:
   shape=f'<path d="M250 150 278 135Q300 170 322 135L350 150 370 280 342 320 440 655Q300 690 160 655L258 320 230 280Z" fill="{c}"/><path d="M258 320H342M278 135Q300 205 322 135M245 390 205 640M355 390 395 640" stroke="#00000020" stroke-width="3" fill="none"/>'
  else:
   shape=f'<path d="M230 160 270 140H330L370 160 435 260 400 500 365 490 370 640H230L235 490 200 500 165 260Z" fill="{c}"/><path d="M270 140 300 250 330 140M300 250V640M235 480H280M320 480H365" stroke="#00000033" stroke-width="4" fill="none"/><path d="M270 140 250 220 280 245 300 280 320 245 350 220 330 140" fill="#ffffff22"/><g fill="#675e51"><circle cx="305" cy="350" r="4"/><circle cx="305" cy="420" r="4"/><circle cx="305" cy="490" r="4"/></g>'
  svg=f'<svg xmlns="http://www.w3.org/2000/svg" width="600" height="800" viewBox="0 0 600 800"><defs><filter id="s"><feDropShadow dx="0" dy="18" stdDeviation="12" flood-opacity=".12"/></filter><pattern id="grain" width="4" height="4" patternUnits="userSpaceOnUse"><circle cx="1" cy="1" r=".4" fill="#000" opacity=".08"/></pattern></defs><rect width="600" height="800" fill="{["#f0eeea","#eaece7","#ede6e0","#e8edf0"][g]}"/><ellipse cx="300" cy="710" rx="170" ry="18" fill="#000" opacity=".04"/><g filter="url(#s)">{shape}</g><rect width="600" height="800" fill="url(#grain)"/><text x="35" y="755" font-family="Arial" font-size="12" letter-spacing="4" fill="#7b7a70">KH / ESSENTIALS — {n:02d}</text></svg>'
  (out/f'product-{n}.svg').write_text(svg)
  (out/f'product-{n}-detail.svg').write_text(svg.replace('viewBox="0 0 600 800"','viewBox="170 170 260 345"'))
