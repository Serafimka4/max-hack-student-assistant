import { useState } from 'react'
import { Icon } from '../components/Icon'
import { PageHeader, TicketBadge } from '../components/ui'
import { faq, faqCategories, templates } from '../data/demo'
import { useStore } from '../store'

export function Help() {
  const { tickets, openReport, notify } = useStore()
  const [query, setQuery] = useState('')
  const [cat, setCat] = useState<(typeof faqCategories)[number]>('Все')
  const [open, setOpen] = useState<string | null>(faq[1].id)
  const [rated, setRated] = useState<Record<string, boolean>>({})

  const q = query.trim().toLowerCase()
  const list = faq.filter(f => (cat === 'Все' || f.cat === cat) && (!q || (f.q + f.a).toLowerCase().includes(q)))

  return (
    <div className="screen">
      <PageHeader eyebrow={<span className="badge badge-outline-lime">Вопросы · документы · обращения</span>} title="Помощь" />
      <div className="dark" style={{ padding: '0 16px 18px' }}>
        <div className="search" style={{ background: 'var(--ink-2)', borderColor: 'var(--ink-line)', color: '#fff' }}>
          <Icon name="search" size={18} />
          <input placeholder="Справка, практика, стипендия…" value={query} onChange={e => setQuery(e.target.value)} aria-label="Поиск по вопросам" />
        </div>
      </div>

      {/* Обращения */}
      <div className="section">
        <div className="section-head">
          <h2 className="display h2">Мои обращения</h2>
          <button className="link" onClick={() => openReport({ title: 'Новое обращение', context: 'Без привязки к разделу', owner: 'Координатор пилота' })}>
            Создать <Icon name="right" size={14} />
          </button>
        </div>
        <div className="ticket-scroll">
          {tickets.map(t => (
            <div key={t.id} className="card ticket">
              <TicketBadge status={t.status} />
              <div style={{ fontWeight: 800, marginTop: 10, lineHeight: 1.3 }}>{t.title}</div>
              <div className="tiny muted" style={{ marginTop: 4 }}>{t.context}</div>
              <div className="divider" style={{ margin: '10px 0' }} />
              <div className="row between tiny">
                <span style={{ fontWeight: 700 }}>{t.owner}</span>
                <span className="muted">{t.due}</span>
              </div>
              {t.status === 'Решено' && (
                <button className="btn btn-lime btn-block" style={{ minHeight: 34, marginTop: 10, fontSize: 12 }}
                  onClick={() => notify('Спасибо! Решение подтверждено')}>Подтвердить решение</button>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* FAQ */}
      <div className="section">
        <div className="section-head"><h2 className="display h2">Частые вопросы</h2></div>
        <div className="chips" style={{ marginBottom: 12 }}>
          {faqCategories.map(c => (
            <button key={c} className={`chip ${cat === c ? 'active' : ''}`} onClick={() => setCat(c)}>{c}</button>
          ))}
        </div>
        <div className="card" style={{ padding: 0 }}>
          {list.length === 0 && (
            <div style={{ padding: 16 }}>
              <p className="muted small">Ничего не нашлось.</p>
              <button className="btn btn-ink" style={{ marginTop: 10 }}
                onClick={() => openReport({ title: 'Задать вопрос', context: `Поиск: «${query}»`, owner: 'Деканат ИнПИТ' })}>Задать вопрос</button>
            </div>
          )}
          {list.map((f, i) => {
            const isOpen = open === f.id
            return (
              <div key={f.id} style={{ borderTop: i ? '1px solid var(--line)' : 0 }}>
                <button className="faq-q" aria-expanded={isOpen} onClick={() => setOpen(isOpen ? null : f.id)}>
                  <span>{f.q}</span>
                  <span className={`faq-toggle ${isOpen ? 'open' : ''}`}><Icon name="down" size={16} /></span>
                </button>
                {isOpen && (
                  <div style={{ padding: '0 16px 16px' }}>
                    <p style={{ fontSize: 14 }}>{f.a}</p>
                    <div className="tiny muted" style={{ marginTop: 8 }}>{f.owner} · обновлено {f.updated}</div>
                    {rated[f.id] ? (
                      <div className="row small" style={{ marginTop: 12, fontWeight: 700 }}><Icon name="check" size={16} />Спасибо за оценку</div>
                    ) : (
                      <div className="row" style={{ marginTop: 12 }}>
                        <button className="btn btn-lime" style={{ minHeight: 36, fontSize: 13 }} onClick={() => setRated(r => ({ ...r, [f.id]: true }))}>Помогло</button>
                        <button className="btn btn-ghost" style={{ minHeight: 36, fontSize: 13 }}
                          onClick={() => openReport({ title: 'Ответ не помог', context: `FAQ · ${f.q}`, owner: f.owner })}>Нужна помощь</button>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )
          })}
        </div>
      </div>

      {/* Шаблоны */}
      <div className="section">
        <div className="section-head"><h2 className="display h2">Шаблоны</h2></div>
        <div className="stack">
          {templates.map((t, i) => (
            <div key={t.id} className="card row" style={{ gap: 12 }}>
              <span className={`tpl-ic ${['bg-coral', 'bg-lime', 'bg-blue'][i % 3]}`}><Icon name="file" size={20} /></span>
              <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontWeight: 800 }}>{t.title}</div>
                <div className="tiny muted">{t.meta} · {t.owner}</div>
              </div>
              <button className="icon-btn on-light" aria-label={`Скачать: ${t.title}`} onClick={() => notify(`Скачивание: ${t.title}`)}>
                <Icon name="download" size={18} />
              </button>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
