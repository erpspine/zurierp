import { useState, useEffect } from 'react'
import Swal from 'sweetalert2'
import './SubscriptionModal.css'

const BILLING_CYCLES = [
  { value: 'monthly',     label: 'Monthly',      months: 1  },
  { value: 'quarterly',   label: 'Quarterly (3 months)', months: 3  },
  { value: 'semi_annual', label: 'Semi-Annual (6 months)', months: 6 },
  { value: 'annual',      label: 'Annual (12 months)', months: 12 },
]

const PAYMENT_METHODS = [
  { value: 'cash',           label: '💵 Cash' },
  { value: 'bank_transfer',  label: '🏦 Bank Transfer' },
  { value: 'mobile_money',   label: '📱 Mobile Money' },
  { value: 'card',           label: '💳 Card' },
  { value: 'cheque',         label: '📄 Cheque' },
  { value: 'other',          label: '🔧 Other' },
]

const CURRENCIES = ['USD', 'TZS', 'EUR', 'GBP', 'KES', 'UGX', 'RWF', 'ZAR']

function formatDisplayDate(value) {
  if (!value) return '—'

  const stringValue = String(value).trim()

  if (/^\d{4}-\d{2}-\d{2}$/.test(stringValue)) {
    const [year, month, day] = stringValue.split('-')
    return `${day}/${month}/${year}`
  }

  const date = new Date(stringValue)

  return Number.isNaN(date.getTime())
    ? stringValue
    : new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      }).format(date)
}

function addMonths(dateStr, months) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  d.setMonth(d.getMonth() + months)
  d.setDate(d.getDate() - 1)
  return d.toISOString().slice(0, 10)
}

export default function SubscriptionModal({ apiBaseUrl, apiToken, companies, plans, onClose, onCreated }) {
  const [isSaving, setIsSaving] = useState(false)
  const [error, setError]       = useState('')

  const today = new Date().toISOString().slice(0, 10)

  const [form, setForm] = useState({
    company_id:        '',
    plan_id:           '',
    billing_cycle:     'monthly',
    duration_months:   '1',
    starts_at:         today,
    amount_paid:       '',
    currency:          'USD',
    payment_method:    'bank_transfer',
    payment_reference: '',
    payment_notes:     '',
    payment_date:      today,
  })

  // Derived: computed end date
  const selectedCycle = BILLING_CYCLES.find(c => c.value === form.billing_cycle)
  const durationMonths = form.billing_cycle === 'monthly'
    ? Math.max(1, Number.parseInt(form.duration_months || '1', 10) || 1)
    : (selectedCycle?.months ?? 1)
  const endsAt = addMonths(form.starts_at, durationMonths)

  // Auto-fill amount from plan price when plan + cycle change
  useEffect(() => {
    if (!form.plan_id) return
    const plan = plans.find(p => String(p.id) === String(form.plan_id))
    if (!plan || !plan.monthly_price) return
    setForm(p => ({ ...p, amount_paid: (parseFloat(plan.monthly_price) * durationMonths).toFixed(2) }))
  }, [form.plan_id, form.billing_cycle, form.duration_months])

  useEffect(() => {
    if (form.billing_cycle === 'monthly') return
    const cycle = BILLING_CYCLES.find(c => c.value === form.billing_cycle)
    const fixedMonths = String(cycle?.months ?? 1)
    setForm(prev => (prev.duration_months === fixedMonths ? prev : { ...prev, duration_months: fixedMonths }))
  }, [form.billing_cycle])

  function set(field) {
    return (e) => setForm(p => ({ ...p, [field]: e.target.value }))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    if (!form.company_id) { setError('Please select a company.'); return }
    if (!form.amount_paid || isNaN(Number(form.amount_paid))) { setError('Amount paid is required.'); return }

    setIsSaving(true)
    setError('')

    try {
      const res = await fetch(`${apiBaseUrl}/admin/subscriptions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${apiToken}`,
        },
        body: JSON.stringify({ ...form, duration_months: durationMonths, plan_id: form.plan_id || null }),
      })
      const result = await res.json()
      if (!res.ok) {
        const msgs = result.errors
          ? Object.values(result.errors).flat().join(' ')
          : (result.message || 'Could not create subscription.')
        throw new Error(msgs)
      }

      await Swal.fire({
        icon: 'success',
        title: 'Subscription Activated',
        html: `
          <div style="text-align:left;line-height:1.65">
            <div><strong>Company:</strong> ${result.subscription?.company?.name || selectedCompany?.name || 'Selected company'}</div>
            <div><strong>License:</strong> <code>${result.subscription?.license_key || 'Generated'}</code></div>
            <div><strong>Valid Until:</strong> ${formatDisplayDate(result.subscription?.ends_at || endsAt)}</div>
          </div>
        `,
        confirmButtonText: 'Great',
        confirmButtonColor: '#1a6645',
      })

      onCreated(result.subscription)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Could not create subscription.')
    } finally {
      setIsSaving(false)
    }
  }

  const selectedCompany = companies.find(c => c.id === form.company_id)

  return (
    <div className="sm-overlay" role="dialog" aria-modal="true" onClick={e => { if (e.target === e.currentTarget) onClose() }}>
      <div className="sm-modal">

        {/* Header */}
        <div className="sm-header">
          <div className="sm-header-icon">🔑</div>
          <div className="sm-header-info">
            <h2>New Subscription</h2>
            <p>Activate a company licence &amp; record payment</p>
          </div>
          <button type="button" className="sm-close" onClick={onClose} aria-label="Close">✕</button>
        </div>

        <form className="sm-body" id="sm-form" onSubmit={handleSubmit}>

          {/* Company & Plan */}
          <section className="sm-section">
            <div className="sm-section-title"><span>🏢</span> Company &amp; Plan</div>
            <div className="sm-section-body">
              <div className="sm-grid">
                <label className="sm-field sm-field-full">
                  <span className="sm-label">Company <em>*</em></span>
                  <select value={form.company_id} onChange={set('company_id')} required>
                    <option value="">— Select company —</option>
                    {companies.map(c => (
                      <option key={c.id} value={c.id}>{c.name} ({c.company_code || '—'})</option>
                    ))}
                  </select>
                </label>
                <label className="sm-field sm-field-full">
                  <span className="sm-label">Plan</span>
                  <select value={form.plan_id} onChange={set('plan_id')}>
                    <option value="">— No plan / custom —</option>
                    {plans.filter(p => p.is_active).map(p => (
                      <option key={p.id} value={p.id}>
                        {p.name}{p.monthly_price ? ` — ${p.monthly_price}/mo` : ''}
                      </option>
                    ))}
                  </select>
                </label>
              </div>
              {selectedCompany && (
                <div className="sm-company-badge">
                  <span className="sm-badge-code">{selectedCompany.company_code || '—'}</span>
                  <span>{selectedCompany.name}</span>
                  {selectedCompany.email && <span className="sm-badge-sub">{selectedCompany.email}</span>}
                </div>
              )}
            </div>
          </section>

          {/* Subscription Period */}
          <section className="sm-section">
            <div className="sm-section-title"><span>📅</span> Subscription Period</div>
            <div className="sm-section-body">
              <div className="sm-grid">
                <label className="sm-field">
                  <span className="sm-label">Billing Cycle <em>*</em></span>
                  <select value={form.billing_cycle} onChange={set('billing_cycle')}>
                    {BILLING_CYCLES.map(c => <option key={c.value} value={c.value}>{c.label}</option>)}
                  </select>
                </label>
                <label className="sm-field">
                  <span className="sm-label">Duration (Months) <em>*</em></span>
                  <input
                    type="number"
                    min="1"
                    max="60"
                    value={form.duration_months}
                    onChange={set('duration_months')}
                    disabled={form.billing_cycle !== 'monthly'}
                    required
                  />
                </label>
                <label className="sm-field">
                  <span className="sm-label">Start Date <em>*</em></span>
                  <input type="date" value={form.starts_at} onChange={set('starts_at')} required />
                </label>
              </div>
              <div className="sm-period-display">
                <div className="sm-period-row">
                  <span>Start</span>
                  <strong>{formatDisplayDate(form.starts_at)}</strong>
                </div>
                <div className="sm-period-arrow">→</div>
                <div className="sm-period-row">
                  <span>Expiry</span>
                  <strong className="sm-expiry">{formatDisplayDate(endsAt)}</strong>
                </div>
                <div className="sm-period-duration">
                  {durationMonths} month{durationMonths > 1 ? 's' : ''}
                </div>
              </div>
            </div>
          </section>

          {/* Payment Details */}
          <section className="sm-section">
            <div className="sm-section-title"><span>💰</span> Payment Details</div>
            <div className="sm-section-body">
              <div className="sm-grid">
                <label className="sm-field">
                  <span className="sm-label">Amount Paid <em>*</em></span>
                  <div className="sm-amount-wrap">
                    <select className="sm-currency-select" value={form.currency} onChange={set('currency')}>
                      {CURRENCIES.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      className="sm-amount-input"
                      value={form.amount_paid}
                      onChange={set('amount_paid')}
                      placeholder="0.00"
                      required
                    />
                  </div>
                </label>
                <label className="sm-field">
                  <span className="sm-label">Payment Method <em>*</em></span>
                  <select value={form.payment_method} onChange={set('payment_method')} required>
                    {PAYMENT_METHODS.map(m => <option key={m.value} value={m.value}>{m.label}</option>)}
                  </select>
                </label>
                <label className="sm-field">
                  <span className="sm-label">Payment Date</span>
                  <input type="date" value={form.payment_date} onChange={set('payment_date')} />
                </label>
                <label className="sm-field">
                  <span className="sm-label">Reference / Receipt No.</span>
                  <input
                    type="text"
                    value={form.payment_reference}
                    onChange={set('payment_reference')}
                    placeholder="Tx ID, cheque no., receipt…"
                  />
                </label>
                <label className="sm-field sm-field-full">
                  <span className="sm-label">Payment Notes</span>
                  <textarea
                    rows={2}
                    value={form.payment_notes}
                    onChange={set('payment_notes')}
                    placeholder="Any additional payment notes…"
                  />
                </label>
              </div>
            </div>
          </section>

          {/* License Preview */}
          <div className="sm-license-preview">
            <span className="sm-license-icon">🔑</span>
            <div className="sm-license-text">
              <span className="sm-license-label">License key will be auto-generated on save</span>
              <span className="sm-license-format">Format: ZL-{'{COMPANY_CODE}'}-{'{YYYYMM}'}-{'{RANDOM}'}</span>
            </div>
          </div>

          {error ? <p className="sm-error">{error}</p> : null}
        </form>

        {/* Footer */}
        <div className="sm-footer">
          <button type="button" className="ghost-btn" onClick={onClose}>Cancel</button>
          <button type="submit" form="sm-form" className="primary-btn" disabled={isSaving}>
            {isSaving ? 'Activating…' : '✓ Activate Subscription'}
          </button>
        </div>
      </div>
    </div>
  )
}
