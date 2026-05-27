import { useEffect, useMemo, useState } from 'react'
import Swal from 'sweetalert2'
import logo from './assets/zuri-logo.png'
import SidebarMui from './SidebarMui';
import './App.css'
import CompanyWizard from './CompanyWizard'
import CompanyEditModal from './CompanyEditModal'
import SubscriptionModal from './SubscriptionModal'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8099/api'

const dateFormatter = new Intl.DateTimeFormat('en-GB', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
})

const dateTimeFormatter = new Intl.DateTimeFormat('en-GB', {
  day: '2-digit',
  month: '2-digit',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  hour12: false,
})

function formatDisplayDate(value) {
  if (!value) {
    return '—'
  }

  const stringValue = String(value).trim()

  if (/^\d{4}-\d{2}-\d{2}$/.test(stringValue)) {
    const [year, month, day] = stringValue.split('-')
    return `${day}/${month}/${year}`
  }

  const date = new Date(stringValue)
  return Number.isNaN(date.getTime()) ? stringValue : dateFormatter.format(date)
}

function formatDisplayDateTime(value) {
  if (!value) {
    return '—'
  }

  const stringValue = String(value).trim()

  if (/^\d{4}-\d{2}-\d{2}$/.test(stringValue)) {
    return formatDisplayDate(stringValue)
  }

  const date = new Date(stringValue)
  return Number.isNaN(date.getTime()) ? stringValue : dateTimeFormatter.format(date)
}

function App() {
  const [isMenuCollapsed, setIsMenuCollapsed] = useState(false)
  const [activePage, setActivePage] = useState('dashboard')
  const [authState, setAuthState] = useState('checking')
  const [currentUser, setCurrentUser] = useState(null)
  const [isLoggingIn, setIsLoggingIn] = useState(false)
  const [isLoggingOut, setIsLoggingOut] = useState(false)
  const [loginError, setLoginError] = useState('')
  const [loginForm, setLoginForm] = useState({
    email: '',
    password: '',
  })
  const [users, setUsers] = useState([])
  const [roles, setRoles] = useState([])
  const [auditLogs, setAuditLogs] = useState([])
  const [auditMeta, setAuditMeta] = useState({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
  })
  const [isLoadingAuditLogs, setIsLoadingAuditLogs] = useState(false)
  const [auditFilter, setAuditFilter] = useState({
    search: '',
    action: 'all',
    dateFrom: '',
    dateTo: '',
  })
  const [isLoadingUsers, setIsLoadingUsers] = useState(false)
  const [isSavingUser, setIsSavingUser] = useState(false)
  const [isLoadingPlans, setIsLoadingPlans] = useState(false)
  const [isSavingPlan, setIsSavingPlan] = useState(false)
  const [formMessage, setFormMessage] = useState('')
  const [plansMessage, setPlansMessage] = useState('')
  const [apiToken, setApiToken] = useState(localStorage.getItem('platform_admin_token') || '')
  const [editingUserId, setEditingUserId] = useState(null)
  const [editingPlanId, setEditingPlanId] = useState(null)
  const [plans, setPlans] = useState([])
  const [companies, setCompanies] = useState([])
  const [isLoadingCompanies, setIsLoadingCompanies] = useState(false)
  const [showCompanyWizard, setShowCompanyWizard] = useState(false)
  const [viewingCompany, setViewingCompany] = useState(null)
  const [companyUsersFilter, setCompanyUsersFilter] = useState('all')
  const [editingCompany, setEditingCompany] = useState(null)
  const [companiesFilter, setCompaniesFilter] = useState({ search: '', status: 'all', subscription: 'all' })
  const [isLoadingDashboard, setIsLoadingDashboard] = useState(false)

  // Subscriptions
  const [subscriptions, setSubscriptions] = useState([])
  const [isLoadingSubscriptions, setIsLoadingSubscriptions] = useState(false)
  const [showSubscriptionModal, setShowSubscriptionModal] = useState(false)
  const [viewingSubscription, setViewingSubscription] = useState(null)
  const [subscriptionsFilter, setSubscriptionsFilter] = useState({ search: '', status: 'all', company_id: 'all' })
  const [usersFilter, setUsersFilter] = useState({
    search: '',
    status: 'all',
    roleId: 'all',
  })
  const [userForm, setUserForm] = useState({
    name: '',
    email: '',
    password: '',
    status: 'active',
    role_id: '',
  })
  const [planForm, setPlanForm] = useState({
    name: '',
    slug: '',
    subtitle: '',
    monthly_price: '',
    is_custom_pricing: false,
    users_limit: '',
    branches_limit: '',
    vehicles_limit: '',
    bookings_limit: '',
    features_text: '',
    is_featured: false,
    is_active: true,
    sort_order: 0,
  })

  const menuItems = [
    { key: 'dashboard', label: 'Dashboard', short: 'DB' },
    { key: 'companies', label: 'Companies', short: 'CO' },
    { key: 'subscriptions', label: 'Subscriptions', short: 'SU' },
    { key: 'plans', label: 'Plans', short: 'PL' },
    { key: 'platform-users', label: 'Platform Users', short: 'PU' },
    { key: 'audit-logs', label: 'Audit Logs', short: 'AL' },
    { key: 'system-settings', label: 'System Settings', short: 'SS' },
  ]

  const kpis = [
    { label: 'Active Companies', value: '124', delta: '+9 this month' },
    { label: 'MRR', value: '$42,800', delta: '+12.4%' },
    { label: 'Pending Support Cases', value: '17', delta: '-6 today' },
    { label: 'Implementation Pipeline', value: '31', delta: '5 onboarding now' },
  ]

  const dashboardCompanies = [
    { name: 'Savanna Quest', plan: 'Growth', status: 'Active', mrr: '$680' },
    { name: 'Kilimanjaro Trails', plan: 'Enterprise', status: 'Trial', mrr: '$0' },
    { name: 'Nile Horizon Tours', plan: 'Scale', status: 'Active', mrr: '$1,240' },
    { name: 'Atlas Safari Group', plan: 'Starter', status: 'Past Due', mrr: '$290' },
  ]

  const currentPageTitle = useMemo(() => {
    const found = menuItems.find((item) => item.key === activePage)
    return found ? found.label : 'Dashboard'
  }, [activePage])

  const filteredUsers = useMemo(() => {
    const searchValue = usersFilter.search.trim().toLowerCase()

    return users.filter((user) => {
      const userRoles = user.roles || []
      const matchesSearch = !searchValue
        || user.name?.toLowerCase().includes(searchValue)
        || user.email?.toLowerCase().includes(searchValue)

      const matchesStatus = usersFilter.status === 'all'
        || (user.status || 'active') === usersFilter.status

      const matchesRole = usersFilter.roleId === 'all'
        || userRoles.some((role) => String(role.id) === usersFilter.roleId)

      return matchesSearch && matchesStatus && matchesRole
    })
  }, [users, usersFilter])

  const filteredCompanies = useMemo(() => {
    const q = companiesFilter.search.trim().toLowerCase()
    return companies.filter((c) => {
      const matchesSearch = !q
        || c.name?.toLowerCase().includes(q)
        || c.email?.toLowerCase().includes(q)
        || c.company_code?.toLowerCase().includes(q)
        || c.country?.toLowerCase().includes(q)
      const matchesStatus = companiesFilter.status === 'all' || (c.status || 'active').toLowerCase() === companiesFilter.status
      const matchesSub = companiesFilter.subscription === 'all' || (c.subscription_status || 'trial').toLowerCase() === companiesFilter.subscription
      return matchesSearch && matchesStatus && matchesSub
    })
  }, [companies, companiesFilter])

  const filteredViewingCompanyUsers = useMemo(() => {
    if (!viewingCompany || !Array.isArray(viewingCompany.users)) {
      return []
    }

    if (companyUsersFilter === 'inactive') {
      return viewingCompany.users.filter((user) => String(user.status || 'active').toLowerCase() === 'inactive')
    }

    if (companyUsersFilter === 'active') {
      return viewingCompany.users.filter((user) => String(user.status || 'active').toLowerCase() === 'active')
    }

    return viewingCompany.users
  }, [viewingCompany, companyUsersFilter])

  useEffect(() => {
    if (!apiToken) {
      setAuthState('unauthenticated')
      setCurrentUser(null)
      return
    }

    void validateSession(apiToken)
  }, [apiToken])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'platform-users') {
      return
    }

    void loadUsersPageData()
  }, [activePage, authState])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'audit-logs') {
      return
    }

    void loadAuditLogs()
  }, [activePage, authState])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'plans') {
      return
    }

    void loadPlansData()
  }, [activePage, authState])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'companies') {
      return
    }

    void loadCompanies()
  }, [activePage, authState])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'dashboard') {
      return
    }

    void loadDashboardData()
  }, [activePage, authState])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'subscriptions') {
      return
    }

    void loadSubscriptions()
    // Also ensure companies + plans are loaded for the "New Subscription" modal
    if (!companies.length) void loadCompanies()
    if (!plans.length) void loadPlansData()
  }, [activePage, authState])

  useEffect(() => {
    if (authState !== 'authenticated' || activePage !== 'subscriptions') {
      return
    }

    const timer = setTimeout(() => {
      void loadSubscriptions(subscriptionsFilter)
    }, 250)

    return () => clearTimeout(timer)
  }, [subscriptionsFilter, activePage, authState])

  function clearSession() {
    localStorage.removeItem('platform_admin_token')
    setApiToken('')
    setCurrentUser(null)
    setAuthState('unauthenticated')
    setUsers([])
    setRoles([])
    setAuditLogs([])
    setCompanies([])
    setPlans([])
    setActivePage('dashboard')
    setEditingUserId(null)
    setEditingPlanId(null)
    setFormMessage('')
    setPlansMessage('')
  }

  async function validateSession(token) {
    setAuthState('checking')
    setLoginError('')

    try {
      const me = await apiRequest('/admin/me', {
        token,
      })

      setCurrentUser(me)
      setAuthState('authenticated')
    } catch (_error) {
      clearSession()
      setLoginError('Session expired. Please login again.')
    }
  }

  async function apiRequest(path, options = {}) {
    const { token, ...restOptions } = options

    const response = await fetch(`${API_BASE_URL}${path}`, {
      ...restOptions,
      headers: {
        Accept: 'application/json',
        ...(restOptions.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token || apiToken ? { Authorization: `Bearer ${token || apiToken}` } : {}),
        ...(restOptions.headers || {}),
      },
    })

    const contentType = response.headers.get('content-type') || ''
    const data = contentType.includes('application/json') ? await response.json() : null

    if (!response.ok) {
      const message = data?.message || 'Request failed.'

      if (response.status === 401 && path !== '/platform/login') {
        clearSession()
      }

      throw new Error(message)
    }

    return data
  }

  async function loadUsersPageData() {
    setIsLoadingUsers(true)
    setFormMessage('')

    try {
      const [usersData, rolesData] = await Promise.all([
        apiRequest('/admin/users'),
        apiRequest('/admin/platform-roles'),
      ])

      setUsers(Array.isArray(usersData) ? usersData : [])
      setRoles(Array.isArray(rolesData) ? rolesData : [])
    } catch (error) {
      setFormMessage(error instanceof Error ? error.message : 'Could not load users page data.')
    } finally {
      setIsLoadingUsers(false)
    }
  }

  async function loadAuditLogs(page = 1, filterOverride = null) {
    setIsLoadingAuditLogs(true)
    setFormMessage('')

    try {
      const filter = filterOverride || auditFilter
      const params = new URLSearchParams()
      params.set('page', String(page))
      params.set('per_page', '20')

      if (filter.search.trim()) {
        params.set('search', filter.search.trim())
      }

      if (filter.action !== 'all') {
        params.set('action', filter.action)
      }

      if (filter.dateFrom) {
        params.set('date_from', filter.dateFrom)
      }

      if (filter.dateTo) {
        params.set('date_to', filter.dateTo)
      }

      const payload = await apiRequest(`/admin/audit-logs?${params.toString()}`)

      setAuditLogs(Array.isArray(payload?.data) ? payload.data : [])
      setAuditMeta({
        current_page: payload?.meta?.current_page || 1,
        last_page: payload?.meta?.last_page || 1,
        per_page: payload?.meta?.per_page || 20,
        total: payload?.meta?.total || 0,
      })
    } catch (error) {
      setFormMessage(error instanceof Error ? error.message : 'Could not load audit logs.')
    } finally {
      setIsLoadingAuditLogs(false)
    }
  }

  function resetAuditFilters() {
    const reset = {
      search: '',
      action: 'all',
      dateFrom: '',
      dateTo: '',
    }

    setAuditFilter(reset)

    return reset
  }

  function formatAuditDate(value) {
    if (!value) {
      return '-'
    }

    return formatDisplayDateTime(value)
  }

  function formatAuditDetails(eventData) {
    if (!eventData || typeof eventData !== 'object') {
      return '-'
    }

    const json = JSON.stringify(eventData)
    return json.length > 140 ? `${json.slice(0, 140)}...` : json
  }

  function resetForm() {
    setEditingUserId(null)
    setUserForm({
      name: '',
      email: '',
      password: '',
      status: 'active',
      role_id: '',
    })
  }

  function onSelectUserForEdit(user) {
    setEditingUserId(user.id)
    setUserForm({
      name: user.name || '',
      email: user.email || '',
      password: '',
      status: user.status || 'active',
      role_id: (user.roles || [])[0]?.id ? String((user.roles || [])[0].id) : '',
    })
    setFormMessage('')
  }

  async function onDeleteUser(user) {
    const result = await Swal.fire({
      title: 'Delete User?',
      html: `<strong>${user.name}</strong> will be soft-deleted and can be recovered from the database.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c0392b',
      cancelButtonColor: '#1a6645',
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
    })

    if (!result.isConfirmed) return

    setFormMessage('')

    try {
      await apiRequest(`/admin/users/${user.id}`, {
        method: 'DELETE',
      })

      if (editingUserId === user.id) {
        resetForm()
      }

      await loadUsersPageData()
      setFormMessage('User deleted successfully.')
    } catch (error) {
      setFormMessage(error instanceof Error ? error.message : 'Could not delete user.')
    }
  }

  async function onSubmitUser(event) {
    event.preventDefault()
    setIsSavingUser(true)
    setFormMessage('')

    try {
      const payload = {
        name: userForm.name,
        email: userForm.email,
        password: userForm.password,
        status: userForm.status,
        role_id: userForm.role_id ? Number(userForm.role_id) : null,
      }

      if (editingUserId) {
        if (!payload.password) {
          delete payload.password
        }

        await apiRequest(`/admin/users/${editingUserId}`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        })
      } else {
        await apiRequest('/admin/users', {
          method: 'POST',
          body: JSON.stringify(payload),
        })
      }

      await loadUsersPageData()
      resetForm()
      setFormMessage(`User ${editingUserId ? 'updated' : 'created'} successfully.`)
    } catch (error) {
      setFormMessage(error instanceof Error ? error.message : 'Could not save user.')
    } finally {
      setIsSavingUser(false)
    }
  }

  async function onSubmitLogin(event) {
    event.preventDefault()
    setIsLoggingIn(true)
    setLoginError('')

    try {
      const response = await apiRequest('/platform/login', {
        method: 'POST',
        body: JSON.stringify(loginForm),
      })

      if (!response?.token) {
        throw new Error('Invalid login response from server.')
      }

      localStorage.setItem('platform_admin_token', response.token)
      setApiToken(response.token)
      setLoginForm({ email: '', password: '' })
    } catch (error) {
      setLoginError(error instanceof Error ? error.message : 'Login failed.')
    } finally {
      setIsLoggingIn(false)
    }
  }

  async function onLogout() {
    if (!apiToken || isLoggingOut) {
      clearSession()
      return
    }

    setIsLoggingOut(true)

    try {
      await apiRequest('/admin/logout', {
        method: 'POST',
      })
    } catch (_error) {
      // Even if API logout fails, clear local session to protect access.
    } finally {
      clearSession()
      setIsLoggingOut(false)
    }
  }

  function resetPlanForm() {
    setEditingPlanId(null)
    setPlanForm({
      name: '',
      slug: '',
      subtitle: '',
      monthly_price: '',
      is_custom_pricing: false,
      users_limit: '',
      branches_limit: '',
      vehicles_limit: '',
      bookings_limit: '',
      features_text: '',
      is_featured: false,
      is_active: true,
      sort_order: 0,
    })
  }

  function toLimitValue(value) {
    if (value === '' || value === null || value === undefined) {
      return null
    }

    return Number(value)
  }

  function formatLimitValue(value) {
    if (value === null || value === undefined || value === '') {
      return 'Unlimited'
    }

    return String(value)
  }

  function formatPlanPrice(plan) {
    if (plan.is_custom_pricing) {
      return {
        amount: 'Custom',
        period: 'pricing',
      }
    }

    const parsed = Number(plan.monthly_price)
    const amount = Number.isFinite(parsed) ? `$${parsed}` : '$0'

    return {
      amount,
      period: '/month',
    }
  }

  async function loadPlansData() {
    setIsLoadingPlans(true)
    setPlansMessage('')

    try {
      const response = await apiRequest('/admin/plans')
      setPlans(Array.isArray(response) ? response : [])
    } catch (error) {
      setPlansMessage(error instanceof Error ? error.message : 'Could not load plans.')
    } finally {
      setIsLoadingPlans(false)
    }
  }

  function onSelectPlanForEdit(plan) {
    setEditingPlanId(plan.id)
    setPlanForm({
      name: plan.name || '',
      slug: plan.slug || '',
      subtitle: plan.subtitle || '',
      monthly_price: plan.monthly_price ?? '',
      is_custom_pricing: Boolean(plan.is_custom_pricing),
      users_limit: plan.users_limit ?? '',
      branches_limit: plan.branches_limit ?? '',
      vehicles_limit: plan.vehicles_limit ?? '',
      bookings_limit: plan.bookings_limit ?? '',
      features_text: Array.isArray(plan.features) ? plan.features.join('\n') : '',
      is_featured: Boolean(plan.is_featured),
      is_active: plan.is_active !== false,
      sort_order: Number(plan.sort_order || 0),
    })
    setPlansMessage('')
  }

  async function onDeletePlan(plan) {
    const result = await Swal.fire({
      title: 'Delete Plan?',
      html: `Plan <strong>${plan.name}</strong> will be permanently deleted.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c0392b',
      cancelButtonColor: '#1a6645',
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel',
    })

    if (!result.isConfirmed) return

    setPlansMessage('')

    try {
      await apiRequest(`/admin/plans/${plan.id}`, {
        method: 'DELETE',
      })

      if (editingPlanId === plan.id) {
        resetPlanForm()
      }

      await loadPlansData()
      setPlansMessage('Plan deleted successfully.')
    } catch (error) {
      setPlansMessage(error instanceof Error ? error.message : 'Could not delete plan.')
    }
  }

  async function onSubmitPlan(event) {
    event.preventDefault()
    setIsSavingPlan(true)
    setPlansMessage('')
    const wasEditing = Boolean(editingPlanId)

    try {
      const features = planForm.features_text
        .split('\n')
        .map((item) => item.trim())
        .filter(Boolean)

      const payload = {
        name: planForm.name.trim(),
        slug: planForm.slug.trim() || null,
        subtitle: planForm.subtitle.trim() || null,
        monthly_price: planForm.is_custom_pricing ? null : (planForm.monthly_price === '' ? null : Number(planForm.monthly_price)),
        is_custom_pricing: Boolean(planForm.is_custom_pricing),
        users_limit: toLimitValue(planForm.users_limit),
        branches_limit: toLimitValue(planForm.branches_limit),
        vehicles_limit: toLimitValue(planForm.vehicles_limit),
        bookings_limit: toLimitValue(planForm.bookings_limit),
        features,
        is_featured: Boolean(planForm.is_featured),
        is_active: Boolean(planForm.is_active),
        sort_order: Number(planForm.sort_order || 0),
      }

      if (wasEditing) {
        await apiRequest(`/admin/plans/${editingPlanId}`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        })
      } else {
        await apiRequest('/admin/plans', {
          method: 'POST',
          body: JSON.stringify(payload),
        })
      }

      await loadPlansData()
      resetPlanForm()
      setPlansMessage(`Plan ${wasEditing ? 'updated' : 'created'} successfully.`)
    } catch (error) {
      setPlansMessage(error instanceof Error ? error.message : 'Could not save plan.')
    } finally {
      setIsSavingPlan(false)
    }
  }


  async function loadCompanies() {
    setIsLoadingCompanies(true)

    try {
      const response = await apiRequest('/admin/companies')
      setCompanies(Array.isArray(response) ? response : [])
    } catch (_error) {
      // silently fail; user can refresh
    } finally {
      setIsLoadingCompanies(false)
    }
  }

  async function loadDashboardData() {
    setIsLoadingDashboard(true)

    try {
      const [companiesData, plansData, subscriptionsData] = await Promise.all([
        apiRequest('/admin/companies'),
        apiRequest('/admin/plans'),
        apiRequest('/admin/subscriptions'),
      ])

      setCompanies(Array.isArray(companiesData) ? companiesData : [])
      setPlans(Array.isArray(plansData) ? plansData : [])
      setSubscriptions(Array.isArray(subscriptionsData) ? subscriptionsData : [])
    } catch (_error) {
      // Keep dashboard resilient even if one endpoint fails.
    } finally {
      setIsLoadingDashboard(false)
    }
  }

  async function loadSubscriptions(filters = subscriptionsFilter) {
    setIsLoadingSubscriptions(true)

    try {
      const params = new URLSearchParams()
      if (filters.search?.trim()) params.set('search', filters.search.trim())
      if (filters.status && filters.status !== 'all') params.set('status', filters.status)
      if (filters.company_id && filters.company_id !== 'all') params.set('company_id', filters.company_id)

      const query = params.toString()
      const response = await apiRequest(`/admin/subscriptions${query ? `?${query}` : ''}`)
      setSubscriptions(Array.isArray(response) ? response : [])
    } catch (_error) {
      // silently fail
    } finally {
      setIsLoadingSubscriptions(false)
    }
  }

  async function onCancelSubscription(sub) {
    const result = await Swal.fire({
      title: 'Cancel Subscription?',
      html: `This will cancel the subscription for <strong>${sub.company?.name || sub.company_id}</strong> (Licence: <code>${sub.license_key}</code>). The account will lose active status.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, cancel it',
      cancelButtonText: 'Keep active',
      confirmButtonColor: '#c0392b',
    })

    if (!result.isConfirmed) return

    try {
      const data = await apiRequest(`/admin/subscriptions/${sub.id}/cancel`, { method: 'POST' })
      setSubscriptions((prev) => prev.map((s) => s.id === sub.id ? data.subscription : s))
      Swal.fire({ icon: 'success', title: 'Cancelled', text: 'Subscription has been cancelled.', timer: 2000, showConfirmButton: false })
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: err instanceof Error ? err.message : 'Could not cancel.' })
    }
  }

  async function onDownloadInvoice(sub) {
    try {
      const response = await fetch(`${API_BASE_URL}/admin/subscriptions/${sub.id}/invoice`, {
        method: 'GET',
        headers: {
          Accept: 'application/pdf',
          ...(apiToken ? { Authorization: `Bearer ${apiToken}` } : {}),
        },
      })

      if (!response.ok) {
        throw new Error('Could not generate invoice PDF.')
      }

      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const anchor = document.createElement('a')
      anchor.href = url
      const stamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19)
      anchor.download = `invoice-${sub.invoice_number || sub.license_key || sub.id}-${stamp}.pdf`
      document.body.appendChild(anchor)
      anchor.click()
      anchor.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: err instanceof Error ? err.message : 'Invoice download failed.' })
    }
  }

  async function openViewCompany(company) {
    try {
      const full = await apiRequest(`/admin/companies/${company.id}`)
      setCompanyUsersFilter('all')
      setViewingCompany(full)
    } catch (_error) {
      setCompanyUsersFilter('all')
      setViewingCompany(company)
    }
  }

  async function openEditCompany(company) {
    try {
      const full = await apiRequest(`/admin/companies/${company.id}`)
      setEditingCompany(full)
    } catch (_error) {
      setEditingCompany(company)
    }
  }

  async function onDeleteCompany(company) {
    const result = await Swal.fire({
      title: 'Delete Company?',
      html: `<strong>${company.name}</strong> and all associated data will be permanently deleted.<br><br>This action <u>cannot be undone</u>.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#c0392b',
      cancelButtonColor: '#1a6645',
      confirmButtonText: 'Yes, delete permanently',
      cancelButtonText: 'Cancel',
    })

    if (!result.isConfirmed) return

    try {
      await apiRequest(`/admin/companies/${company.id}`, { method: 'DELETE' })
      setCompanies((prev) => prev.filter((c) => c.id !== company.id))
      await Swal.fire({
        title: 'Deleted!',
        text: `${company.name} has been deleted.`,
        icon: 'success',
        confirmButtonColor: '#1a6645',
        timer: 2000,
        timerProgressBar: true,
      })
    } catch (err) {
      await Swal.fire({
        title: 'Error',
        text: err instanceof Error ? err.message : 'Could not delete company.',
        icon: 'error',
        confirmButtonColor: '#1a6645',
      })
    }
  }

  async function onResetCompanyUserPassword(user) {
    if (!viewingCompany?.id || !user?.id) {
      return
    }

    const result = await Swal.fire({
      title: 'Reset user password?',
      html: `A new password will be generated for <strong>${user.name || user.email || 'this user'}</strong> and sent to <strong>${user.email || 'their email'}</strong>.<br><br>Type <strong>RESET</strong> to confirm.`,
      icon: 'warning',
      showCancelButton: true,
      input: 'text',
      inputPlaceholder: 'Type RESET',
      confirmButtonText: 'Yes, reset password',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#1a6645',
      reverseButtons: true,
      focusCancel: true,
      preConfirm: (value) => {
        if (String(value || '').trim().toUpperCase() !== 'RESET') {
          Swal.showValidationMessage('Please type RESET to continue.')
          return false
        }

        return true
      },
    })

    if (!result.isConfirmed) return

    try {
      const response = await apiRequest(`/admin/companies/${viewingCompany.id}/users/${user.id}/reset-password`, {
        method: 'POST',
      })

      await Swal.fire({
        icon: 'success',
        title: 'Password reset',
        text: response?.message || 'A new password has been emailed to the user.',
        confirmButtonColor: '#1a6645',
      })
    } catch (error) {
      await Swal.fire({
        icon: 'error',
        title: 'Could not reset password',
        text: error instanceof Error ? error.message : 'Please try again.',
        confirmButtonColor: '#1a6645',
      })
    }
  }

  async function onToggleCompanyUserStatus(user) {
    if (!viewingCompany?.id || !user?.id) {
      return
    }

    const currentStatus = String(user.status || 'active').toLowerCase()
    const nextStatus = currentStatus === 'active' ? 'inactive' : 'active'
    const actionLabel = nextStatus === 'inactive' ? 'Deactivate' : 'Activate'

    const result = await Swal.fire({
      title: `${actionLabel} user?`,
      html: nextStatus === 'inactive'
        ? `User <strong>${user.name || user.email || 'this account'}</strong> will no longer be able to log in.`
        : `User <strong>${user.name || user.email || 'this account'}</strong> will be allowed to log in again.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: `Yes, ${actionLabel.toLowerCase()}`,
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#1a6645',
      reverseButtons: true,
      focusCancel: true,
    })

    if (!result.isConfirmed) return

    try {
      const response = await apiRequest(`/admin/companies/${viewingCompany.id}/users/${user.id}/status`, {
        method: 'POST',
        body: JSON.stringify({ status: nextStatus }),
      })

      setViewingCompany((prev) => {
        if (!prev) return prev

        return {
          ...prev,
          users: Array.isArray(prev.users)
            ? prev.users.map((item) => (item.id === user.id ? { ...item, ...(response?.user || {}), status: nextStatus } : item))
            : prev.users,
        }
      })

      await Swal.fire({
        icon: 'success',
        title: nextStatus === 'inactive' ? 'User deactivated' : 'User activated',
        text: response?.message || (nextStatus === 'inactive' ? 'User login has been disabled.' : 'User login has been enabled.'),
        confirmButtonColor: '#1a6645',
      })
    } catch (error) {
      await Swal.fire({
        icon: 'error',
        title: `Could not ${actionLabel.toLowerCase()} user`,
        text: error instanceof Error ? error.message : 'Please try again.',
        confirmButtonColor: '#1a6645',
      })
    }
  }

  function renderNoDataShowcase({ icon, title, description, examples, actionLabel, onAction }) {
    return (
      <div className="empty-state empty-state-rich">
        <div className="empty-state-icon">{icon}</div>
        <h3>{title}</h3>
        <p>{description}</p>
        <div className="empty-example-list" role="list">
          {examples.map((example) => (
            <div className="empty-example-item" role="listitem" key={example.label}>
              <span className="empty-example-label">{example.label}</span>
              <strong>{example.value}</strong>
            </div>
          ))}
        </div>
        {actionLabel && onAction ? (
          <button type="button" className="primary-btn" onClick={onAction}>
            {actionLabel}
          </button>
        ) : null}
      </div>
    )
  }

  function renderSubscriptionsPage() {
    const STATUS_LABELS = {
      active:    { label: 'Active',    color: '#1a6645', bg: '#e8f5ee' },
      trial:     { label: 'Trial',     color: '#7a5c00', bg: '#fff8e0' },
      expired:   { label: 'Expired',   color: '#5f7568', bg: '#f1f5f2' },
      cancelled: { label: 'Cancelled', color: '#c0392b', bg: '#fdf2f2' },
      suspended: { label: 'Suspended', color: '#8a4200', bg: '#fff0e0' },
    }

    const getExpiryMeta = (endsAt) => {
      if (!endsAt) {
        return { text: 'No expiry date', color: '#8a8a8a' }
      }

      const endDate = new Date(String(endsAt).slice(0, 10))
      const today = new Date()
      const todayAtMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate())
      const msPerDay = 1000 * 60 * 60 * 24
      const diffDays = Math.ceil((endDate.getTime() - todayAtMidnight.getTime()) / msPerDay)

      if (diffDays < 0) {
        const daysAgo = Math.abs(diffDays)
        return {
          text: `Expired ${daysAgo} day${daysAgo === 1 ? '' : 's'} ago`,
          color: '#c0392b',
        }
      }

      if (diffDays === 0) {
        return { text: 'Expires today', color: '#8a4200' }
      }

      return {
        text: `In ${diffDays} day${diffDays === 1 ? '' : 's'}`,
        color: '#1a6645',
      }
    }

    return (
      <section className="users-page reveal-3">
        <article className="panel users-toolbar company-gradient-toolbar">
          <h3>Subscriptions</h3>
          <div className="users-actions">
            <button type="button" className="ghost-btn" onClick={() => loadSubscriptions(subscriptionsFilter)}>Refresh</button>
            <button type="button" className="primary-btn" onClick={() => setShowSubscriptionModal(true)}>
              + New Subscription
            </button>
          </div>
        </article>

        <article className="panel">
          <div className="users-filters">
            <label>
              Search
              <input
                type="text"
                placeholder="License, company, plan…"
                value={subscriptionsFilter.search}
                onChange={e => setSubscriptionsFilter(p => ({ ...p, search: e.target.value }))}
              />
            </label>
            <label>
              Status
              <select value={subscriptionsFilter.status} onChange={e => setSubscriptionsFilter(p => ({ ...p, status: e.target.value }))}>
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="trial">Trial</option>
                <option value="expired">Expired</option>
                <option value="cancelled">Cancelled</option>
                <option value="suspended">Suspended</option>
              </select>
            </label>
            <label>
              Company
              <select value={subscriptionsFilter.company_id} onChange={e => setSubscriptionsFilter(p => ({ ...p, company_id: e.target.value }))}>
                <option value="all">All companies</option>
                {companies.map(company => (
                  <option key={company.id} value={company.id}>{company.name}</option>
                ))}
              </select>
            </label>
          </div>

          {isLoadingSubscriptions ? (
            <p className="loading-text">Loading subscriptions…</p>
          ) : subscriptions.length === 0 ? (
            renderNoDataShowcase({
              icon: '🧾',
              title: 'No subscriptions yet',
              description: 'Create your first subscription to start tracking plan activations and billing cycles.',
              examples: [
                { label: 'Company', value: 'Savanna Quest Ltd' },
                { label: 'Plan', value: 'Growth Plan (Monthly)' },
                { label: 'Status', value: 'Trial -> Active' },
              ],
              actionLabel: '+ New Subscription',
              onAction: () => setShowSubscriptionModal(true),
            })
          ) : (
            <div className="table-scroll-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>License Key</th>
                    <th>Company</th>
                    <th>Plan</th>
                    <th>Cycle</th>
                    <th>Status</th>
                    <th>Starts</th>
                    <th>Expires</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {subscriptions.map(sub => {
                    const st = STATUS_LABELS[sub.status] || { label: sub.status, color: '#333', bg: '#eee' }
                    return (
                      <tr key={sub.id}>
                        <td>
                          <code style={{ fontSize: '0.78rem', background: '#edf7f1', padding: '2px 7px', borderRadius: '5px' }}>
                            {sub.license_key}
                          </code>
                        </td>
                        <td>{sub.company?.name || sub.company_id}</td>
                        <td>{sub.plan?.name || <em style={{ color: '#aaa' }}>—</em>}</td>
                        <td style={{ textTransform: 'capitalize' }}>{(sub.billing_cycle || '').replace('_', ' ')}</td>
                        <td>
                          <span style={{
                            display: 'inline-block',
                            padding: '2px 10px',
                            borderRadius: '20px',
                            fontSize: '0.78rem',
                            fontWeight: 700,
                            color: st.color,
                            background: st.bg,
                          }}>
                            {st.label}
                          </span>
                        </td>
                        <td>{formatDisplayDate(sub.starts_at)}</td>
                        <td>
                          <div>{formatDisplayDate(sub.ends_at)}</div>
                          <div style={{ fontSize: '0.75rem', color: getExpiryMeta(sub.ends_at).color, marginTop: '2px', fontWeight: 600 }}>
                            {getExpiryMeta(sub.ends_at).text}
                          </div>
                        </td>
                        <td>
                          {sub.amount_paid
                            ? `${sub.currency || 'USD'} ${Number(sub.amount_paid).toLocaleString(undefined, { minimumFractionDigits: 2 })}`
                            : '—'}
                        </td>
                        <td style={{ textTransform: 'capitalize' }}>{(sub.payment_method || '—').replace('_', ' ')}</td>
                        <td>
                          <button
                            type="button"
                            className="ghost-btn"
                            style={{ fontSize: '0.78rem', padding: '4px 10px', marginRight: '6px' }}
                            onClick={() => onDownloadInvoice(sub)}
                          >
                            Invoice PDF
                          </button>
                          {sub.status === 'active' || sub.status === 'trial' ? (
                            <button
                              type="button"
                              className="ghost-btn"
                              style={{ color: '#c0392b', borderColor: '#c0392b', fontSize: '0.78rem', padding: '4px 10px' }}
                              onClick={() => onCancelSubscription(sub)}
                            >
                              Cancel
                            </button>
                          ) : (
                            <span style={{ color: '#bbb', fontSize: '0.82rem' }}>—</span>
                          )}
                        </td>
                      </tr>
                    )
                  })}
                </tbody>
              </table>
            </div>
          )}
        </article>
      </section>
    )
  }

  function renderCompaniesPage() {
    return (
      <section className="users-page reveal-3">
        <article className="panel users-toolbar company-gradient-toolbar">
          <h3>Companies</h3>
          <div className="users-actions">
            <button type="button" className="ghost-btn" onClick={loadCompanies}>Refresh</button>
            <button type="button" className="primary-btn" onClick={() => setShowCompanyWizard(true)}>
              + New Company
            </button>
          </div>
        </article>

        <article className="panel">
          <div className="users-filters">
            <label>
              Search
              <input
                type="text"
                placeholder="Name, email, code, country…"
                value={companiesFilter.search}
                onChange={(e) => setCompaniesFilter((p) => ({ ...p, search: e.target.value }))}
              />
            </label>
            <label>
              Status
              <select
                value={companiesFilter.status}
                onChange={(e) => setCompaniesFilter((p) => ({ ...p, status: e.target.value }))}
              >
                <option value="all">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </label>
            <label>
              Subscription
              <select
                value={companiesFilter.subscription}
                onChange={(e) => setCompaniesFilter((p) => ({ ...p, subscription: e.target.value }))}
              >
                <option value="all">All subscriptions</option>
                <option value="trial">Trial</option>
                <option value="active">Active</option>
                <option value="past_due">Past Due</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </label>
            {(companiesFilter.search || companiesFilter.status !== 'all' || companiesFilter.subscription !== 'all') ? (
              <button
                type="button"
                className="ghost-btn"
                onClick={() => setCompaniesFilter({ search: '', status: 'all', subscription: 'all' })}
              >
                Clear filters
              </button>
            ) : null}
          </div>
        </article>

        <section className="panel table-panel">
          {isLoadingCompanies ? (
            <div className="empty-state">
              <div className="empty-state-icon">⏳</div>
              <h3>Loading companies…</h3>
            </div>
          ) : !companies.length ? (
            renderNoDataShowcase({
              icon: '🏢',
              title: 'No companies yet',
              description: 'Add your first tenant company to onboard users, subscriptions, and billing data.',
              examples: [
                { label: 'Company Code', value: 'ZURI-001' },
                { label: 'Industry', value: 'Travel & Tours' },
                { label: 'Subscription', value: 'Trial' },
              ],
              actionLabel: '+ New Company',
              onAction: () => setShowCompanyWizard(true),
            })
          ) : !filteredCompanies.length ? (
            <div className="empty-state">
              <div className="empty-state-icon">🔍</div>
              <h3>No results match your filters</h3>
              <p>Try adjusting the search or filter values.</p>
              <button
                type="button"
                className="ghost-btn"
                onClick={() => setCompaniesFilter({ search: '', status: 'all', subscription: 'all' })}
              >
                Clear filters
              </button>
            </div>
          ) : (
          <div className="table-wrap users-table-wrap">
            <table className="users-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Company Name</th>
                  <th>Industry</th>
                  <th>Status</th>
                  <th>Subscription</th>
                  <th>Location</th>
                  <th>Email</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {filteredCompanies.map((company) => (
                  <tr key={company.id}>
                    <td>
                      <span className="badge active">{company.company_code || '—'}</span>
                    </td>
                    <td><strong>{company.name}</strong></td>
                    <td>{company.industry || '—'}</td>
                    <td>
                      <span className={`badge ${(company.status || 'active').toLowerCase()}`}>
                        {company.status || 'active'}
                      </span>
                    </td>
                    <td>
                      <span className={`badge ${(company.subscription_status || 'trial').toLowerCase().replace('_', '-')}`}>
                        {company.subscription_status || 'trial'}
                      </span>
                    </td>
                    <td>{[company.city, company.country].filter(Boolean).join(', ') || '—'}</td>
                    <td>{company.email || '—'}</td>
                    <td>{formatDisplayDate(company.created_at)}</td>
                    <td>
                      <div className="row-actions">
                        <button type="button" className="row-action-btn view" title="View" onClick={() => openViewCompany(company)}>👁</button>
                        <button type="button" className="row-action-btn edit" title="Edit" onClick={() => openEditCompany(company)}>✏️</button>
                        <button type="button" className="row-action-btn delete" title="Delete" onClick={() => onDeleteCompany(company)}>🗑</button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          )}
        </section>
      </section>
    )
  }

  function renderDashboard() {
    const activeCompanies = companies.filter((c) => String(c.status || '').toLowerCase() === 'active')
    const activeSubscriptions = subscriptions.filter((s) => ['active', 'trial'].includes(String(s.status || '').toLowerCase()))
    const expiredSubscriptions = subscriptions.filter((s) => ['expired', 'cancelled', 'suspended'].includes(String(s.status || '').toLowerCase()))
    const today = new Date()
    const days30Ms = 1000 * 60 * 60 * 24 * 30

    const expiringSoon = subscriptions.filter((s) => {
      if (!s.ends_at) return false
      const endDate = new Date(String(s.ends_at).slice(0, 10))
      const diff = endDate.getTime() - today.getTime()
      return diff >= 0 && diff <= days30Ms
    })

    const totalRevenue = subscriptions.reduce((sum, s) => sum + (Number(s.amount_paid) || 0), 0)
    const activePlansCount = plans.filter((p) => p.is_active !== false).length

    const healthPercent = companies.length
      ? Math.round((activeCompanies.length / companies.length) * 100)
      : 0

    const trialPercent = subscriptions.length
      ? Math.round((subscriptions.filter((s) => String(s.status || '').toLowerCase() === 'trial').length / subscriptions.length) * 100)
      : 0

    const riskPercent = subscriptions.length
      ? Math.round((expiredSubscriptions.length / subscriptions.length) * 100)
      : 0

    const kpiItems = [
      {
        label: 'Active Companies',
        value: String(activeCompanies.length),
        delta: `${companies.length} total companies`,
      },
      {
        label: 'Active Licences',
        value: String(activeSubscriptions.length),
        delta: `${expiringSoon.length} expiring in 30 days`,
      },
      {
        label: 'Revenue Captured',
        value: `${totalRevenue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
        delta: 'Sum of recorded subscription payments',
      },
      {
        label: 'Active Plans',
        value: String(activePlansCount),
        delta: `${plans.length} plans configured`,
      },
    ]

    const recentSubscriptions = [...subscriptions]
      .sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime())
      .slice(0, 4)

    const recentCompanies = [...companies]
      .sort((a, b) => new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime())
      .slice(0, 8)

    const planById = plans.reduce((acc, p) => {
      acc[String(p.id)] = p
      return acc
    }, {})

    return (
      <>
        <section className="kpi-grid reveal-3">
          {kpiItems.map((item) => (
            <article className="kpi-card" key={item.label}>
              <p>{item.label}</p>
              <h2>{item.value}</h2>
              <span>{item.delta}</span>
            </article>
          ))}
        </section>

        <section className="content-grid">
          <article className="panel reveal-4">
            <div className="panel-head">
              <h3>Company billing health</h3>
              <span>{isLoadingDashboard ? 'Refreshing…' : 'Live snapshot'}</span>
            </div>

            <div className="progress-wrap">
              <div className="progress-row">
                <p>Healthy subscriptions</p>
                <strong>{healthPercent}%</strong>
              </div>
              <div className="bar"><span style={{ width: `${healthPercent}%` }} /></div>
            </div>

            <div className="progress-wrap">
              <div className="progress-row">
                <p>Trials converting</p>
                <strong>{trialPercent}%</strong>
              </div>
              <div className="bar"><span style={{ width: `${trialPercent}%` }} /></div>
            </div>

            <div className="progress-wrap">
              <div className="progress-row">
                <p>Overdue invoices</p>
                <strong>{riskPercent}%</strong>
              </div>
              <div className="bar danger"><span style={{ width: `${riskPercent}%` }} /></div>
            </div>
          </article>

          <article className="panel reveal-5">
            <div className="panel-head">
              <h3>Recent platform activity</h3>
            </div>
            <ul className="timeline">
              {recentSubscriptions.length ? recentSubscriptions.map((sub) => (
                <li key={sub.id}>
                  <strong>{sub.company?.name || 'Company'}</strong> subscription
                  {' '}
                  <em style={{ textTransform: 'capitalize' }}>{sub.status || 'active'}</em>
                  {' '}
                  with licence {sub.license_key || 'N/A'}.
                </li>
              )) : (
                <li><strong>No recent activity</strong> yet. Create a subscription to get started.</li>
              )}
            </ul>
          </article>
        </section>

        <section className="panel table-panel reveal-5">
          <div className="panel-head">
            <h3>Companies overview</h3>
            <span>Platform scope only</span>
          </div>

          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Company</th>
                  <th>Plan</th>
                  <th>Status</th>
                  <th>Subscription</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                {recentCompanies.map((company) => {
                  const planName = company.plan?.name
                    || planById[String(company.plan_id)]?.name
                    || '—'

                  return (
                  <tr key={company.id || company.name}>
                    <td>{company.name}</td>
                    <td>{planName}</td>
                    <td>
                      <span className={`badge ${String(company.status || 'active').toLowerCase().replace(' ', '-')}`}>
                        {company.status || 'active'}
                      </span>
                    </td>
                    <td>
                      <span className={`badge ${String(company.subscription_status || 'trial').toLowerCase().replace('_', '-')}`}>
                        {company.subscription_status || 'trial'}
                      </span>
                    </td>
                    <td>{formatDisplayDate(company.created_at)}</td>
                  </tr>
                )})}
                {!recentCompanies.length ? (
                  <tr>
                    <td colSpan={5}>No companies found yet.</td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>
        </section>
      </>
    )
  }

  function renderPlansPage() {
    return (
      <section className="plans-page reveal-3">
        <article className="panel users-toolbar">
          <h3>Plans Management</h3>
          <div className="users-actions">
            <button type="button" className="ghost-btn" onClick={loadPlansData}>Refresh</button>
            <button type="button" className="ghost-btn" onClick={resetPlanForm}>New plan</button>
          </div>
        </article>

        {plansMessage ? <p className="form-message">{plansMessage}</p> : null}

        <div className="plans-manage-grid">
          <article className="panel">
            <h3>{editingPlanId ? 'Edit plan' : 'Create plan'}</h3>

            <form className="plan-form" onSubmit={onSubmitPlan}>
              <label>
                Name
                <input
                  type="text"
                  value={planForm.name}
                  onChange={(event) => setPlanForm((prev) => ({ ...prev, name: event.target.value }))}
                  required
                />
              </label>

              <label>
                Slug (optional)
                <input
                  type="text"
                  value={planForm.slug}
                  onChange={(event) => setPlanForm((prev) => ({ ...prev, slug: event.target.value }))}
                />
              </label>

              <label>
                Subtitle
                <input
                  type="text"
                  value={planForm.subtitle}
                  onChange={(event) => setPlanForm((prev) => ({ ...prev, subtitle: event.target.value }))}
                />
              </label>

              <label>
                Monthly price
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  value={planForm.monthly_price}
                  onChange={(event) => setPlanForm((prev) => ({ ...prev, monthly_price: event.target.value }))}
                  disabled={planForm.is_custom_pricing}
                />
              </label>

              <div className="plan-checks">
                <label>
                  <input
                    type="checkbox"
                    checked={planForm.is_custom_pricing}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, is_custom_pricing: event.target.checked }))}
                  />
                  Custom pricing
                </label>

                <label>
                  <input
                    type="checkbox"
                    checked={planForm.is_featured}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, is_featured: event.target.checked }))}
                  />
                  Featured
                </label>

                <label>
                  <input
                    type="checkbox"
                    checked={planForm.is_active}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, is_active: event.target.checked }))}
                  />
                  Active
                </label>
              </div>

              <div className="plan-limits-grid">
                <label>
                  Users limit
                  <input
                    type="number"
                    min="1"
                    value={planForm.users_limit}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, users_limit: event.target.value }))}
                  />
                </label>

                <label>
                  Branches limit
                  <input
                    type="number"
                    min="1"
                    value={planForm.branches_limit}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, branches_limit: event.target.value }))}
                  />
                </label>

                <label>
                  Vehicles limit
                  <input
                    type="number"
                    min="1"
                    value={planForm.vehicles_limit}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, vehicles_limit: event.target.value }))}
                  />
                </label>

                <label>
                  Bookings limit
                  <input
                    type="number"
                    min="1"
                    value={planForm.bookings_limit}
                    onChange={(event) => setPlanForm((prev) => ({ ...prev, bookings_limit: event.target.value }))}
                  />
                </label>
              </div>

              <label>
                Sort order
                <input
                  type="number"
                  min="0"
                  value={planForm.sort_order}
                  onChange={(event) => setPlanForm((prev) => ({ ...prev, sort_order: event.target.value }))}
                />
              </label>

              <label>
                Features (one per line)
                <textarea
                  rows={8}
                  value={planForm.features_text}
                  onChange={(event) => setPlanForm((prev) => ({ ...prev, features_text: event.target.value }))}
                  placeholder="CRM / Leads&#10;Itinerary Builder&#10;Quotations"
                  required
                />
              </label>

              <div className="form-actions">
                <button type="submit" className="primary-btn" disabled={isSavingPlan}>
                  {isSavingPlan ? 'Saving...' : editingPlanId ? 'Update plan' : 'Create plan'}
                </button>
                {editingPlanId ? (
                  <button type="button" className="ghost-btn" onClick={resetPlanForm}>Cancel edit</button>
                ) : null}
              </div>
            </form>
          </article>

          <div className="plans-grid">
            {isLoadingPlans ? <p>Loading plans...</p> : null}
            {plans.map((plan) => {
              const price = formatPlanPrice(plan)

              return (
                <article key={plan.id} className={`plan-card ${plan.is_featured ? 'featured' : ''}`}>
                  <div className="plan-head">
                    <div>
                      <h4>{plan.name}</h4>
                      <p>{plan.subtitle || 'No subtitle'}</p>
                    </div>
                    {plan.is_featured ? <span className="plan-badge">Most Popular</span> : null}
                  </div>

                  <div className="plan-price">
                    <strong>{price.amount}</strong>
                    <span>{price.period}</span>
                  </div>

                  <div className="plan-limits">
                    <div><span>Users</span><strong>{formatLimitValue(plan.users_limit)}</strong></div>
                    <div><span>Branches</span><strong>{formatLimitValue(plan.branches_limit)}</strong></div>
                    <div><span>Vehicles</span><strong>{formatLimitValue(plan.vehicles_limit)}</strong></div>
                    <div><span>Bookings / month</span><strong>{formatLimitValue(plan.bookings_limit)}</strong></div>
                  </div>

                  <ul className="plan-features">
                    {(plan.features || []).map((feature) => (
                      <li key={`${plan.id}-${feature}`}>{feature}</li>
                    ))}
                  </ul>

                  <div className="plan-card-actions">
                    <button type="button" className="ghost-btn" onClick={() => onSelectPlanForEdit(plan)}>
                      Edit
                    </button>
                    <button type="button" className="ghost-btn delete-btn" onClick={() => onDeletePlan(plan)}>
                      Delete
                    </button>
                  </div>
                </article>
              )
            })}
            {!isLoadingPlans && !plans.length ? (
              renderNoDataShowcase({
                icon: '📦',
                title: 'No plans configured',
                description: 'Define your pricing catalog so new companies can subscribe to the right package.',
                examples: [
                  { label: 'Starter', value: 'USD 29 / month' },
                  { label: 'Growth', value: 'USD 99 / month' },
                  { label: 'Enterprise', value: 'Custom pricing' },
                ],
                actionLabel: 'Create first plan',
                onAction: resetPlanForm,
              })
            ) : null}
          </div>
        </div>
      </section>
    )
  }

  function renderPlatformUsersPage() {
    return (
      <section className="users-page reveal-3">
        <article className="panel users-toolbar">
          <h3>Platform Users</h3>
          <div className="users-actions">
            <button type="button" className="ghost-btn" onClick={loadUsersPageData}>Refresh</button>
            <button type="button" className="ghost-btn" onClick={resetForm}>New user</button>
          </div>
        </article>

        {formMessage ? <p className="form-message">{formMessage}</p> : null}

        <div className="users-grid">
          <article className="panel">
            <h3>{editingUserId ? 'Edit platform user' : 'Create platform user'}</h3>

            <form className="user-form" onSubmit={onSubmitUser}>
              <label>
                Name
                <input
                  type="text"
                  value={userForm.name}
                  onChange={(event) => setUserForm((prev) => ({ ...prev, name: event.target.value }))}
                  required
                />
              </label>

              <label>
                Email
                <input
                  type="email"
                  value={userForm.email}
                  onChange={(event) => setUserForm((prev) => ({ ...prev, email: event.target.value }))}
                  required
                />
              </label>

              <label>
                {editingUserId ? 'New password (optional)' : 'Password'}
                <input
                  type="password"
                  value={userForm.password}
                  onChange={(event) => setUserForm((prev) => ({ ...prev, password: event.target.value }))}
                  required={!editingUserId}
                />
              </label>

              <label>
                Status
                <select
                  value={userForm.status}
                  onChange={(event) => setUserForm((prev) => ({ ...prev, status: event.target.value }))}
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </label>

              <div className="roles-picker">
                <p>Assign role (single role only)</p>
                <select
                  value={userForm.role_id}
                  onChange={(event) => setUserForm((prev) => ({ ...prev, role_id: event.target.value }))}
                >
                  <option value="">No role</option>
                  {roles.map((role) => (
                    <option key={role.id} value={String(role.id)}>{role.name}</option>
                  ))}
                </select>
              </div>

              <div className="form-actions">
                <button type="submit" className="primary-btn" disabled={isSavingUser}>
                  {isSavingUser ? 'Saving...' : editingUserId ? 'Update user' : 'Create user'}
                </button>
                {editingUserId ? (
                  <button type="button" className="ghost-btn" onClick={resetForm}>Cancel edit</button>
                ) : null}
              </div>
            </form>
          </article>

          <article className="panel">
            <h3>Users list</h3>
            {isLoadingUsers ? <p>Loading users...</p> : null}

            <div className="users-filters">
              <label>
                Search
                <input
                  type="text"
                  placeholder="Search name or email"
                  value={usersFilter.search}
                  onChange={(event) => setUsersFilter((prev) => ({ ...prev, search: event.target.value }))}
                />
              </label>

              <label>
                Status
                <select
                  value={usersFilter.status}
                  onChange={(event) => setUsersFilter((prev) => ({ ...prev, status: event.target.value }))}
                >
                  <option value="all">All</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </label>

              <label>
                Role
                <select
                  value={usersFilter.roleId}
                  onChange={(event) => setUsersFilter((prev) => ({ ...prev, roleId: event.target.value }))}
                >
                  <option value="all">All</option>
                  {roles.map((role) => (
                    <option key={role.id} value={String(role.id)}>{role.name}</option>
                  ))}
                </select>
              </label>
            </div>

            {!isLoadingUsers && !users.length ? (
              renderNoDataShowcase({
                icon: '👤',
                title: 'No platform users yet',
                description: 'Create your admin team to manage companies, plans, and subscriptions.',
                examples: [
                  { label: 'Name', value: 'Operations Admin' },
                  { label: 'Email', value: 'ops@zuritours.com' },
                  { label: 'Role', value: 'Super Admin' },
                ],
                actionLabel: 'Create first user',
                onAction: resetForm,
              })
            ) : (
              <div className="table-wrap users-table-wrap">
                <table className="users-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Status</th>
                      <th>Roles</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredUsers.map((user) => (
                      <tr key={user.id}>
                        <td>{user.name}</td>
                        <td>{user.email}</td>
                        <td>
                          <span className={`badge ${(user.status || 'active').toLowerCase()}`}>
                            {user.status || 'active'}
                          </span>
                        </td>
                        <td>{(user.roles || []).map((role) => role.name).join(', ') || '-'}</td>
                        <td>
                          <button type="button" className="ghost-btn" onClick={() => onSelectUserForEdit(user)}>
                            Edit
                          </button>
                          <button type="button" className="ghost-btn delete-btn" onClick={() => onDeleteUser(user)}>
                            Delete
                          </button>
                        </td>
                      </tr>
                    ))}
                    {!filteredUsers.length ? (
                      <tr>
                        <td colSpan="5">No users match your filters.</td>
                      </tr>
                    ) : null}
                  </tbody>
                </table>
              </div>
            )}
          </article>
        </div>
      </section>
    )
  }

  function renderAuditLogsPage() {
    const actionOptions = [
      'platform-auth.login',
      'platform-auth.logout',
      'platform-user.created',
      'platform-user.updated',
      'platform-user.deleted',
    ]

    return (
      <section className="users-page reveal-3">
        <article className="panel users-toolbar">
          <h3>Audit Logs Report</h3>
          <div className="users-actions">
            <button type="button" className="ghost-btn" onClick={() => loadAuditLogs(1)}>Apply filters</button>
            <button type="button" className="ghost-btn" onClick={() => {
              const reset = resetAuditFilters()
              void loadAuditLogs(1, reset)
            }}>
              Reset filters
            </button>
          </div>
        </article>

        {formMessage ? <p className="form-message">{formMessage}</p> : null}

        <article className="panel">
          <div className="users-filters audit-filters">
            <label>
              Search
              <input
                type="text"
                placeholder="Action, target id or IP"
                value={auditFilter.search}
                onChange={(event) => setAuditFilter((prev) => ({ ...prev, search: event.target.value }))}
              />
            </label>

            <label>
              Action
              <select
                value={auditFilter.action}
                onChange={(event) => setAuditFilter((prev) => ({ ...prev, action: event.target.value }))}
              >
                <option value="all">All</option>
                {actionOptions.map((action) => (
                  <option key={action} value={action}>{action}</option>
                ))}
              </select>
            </label>

            <label>
              Date from
              <input
                type="date"
                value={auditFilter.dateFrom}
                onChange={(event) => setAuditFilter((prev) => ({ ...prev, dateFrom: event.target.value }))}
              />
            </label>

            <label>
              Date to
              <input
                type="date"
                value={auditFilter.dateTo}
                onChange={(event) => setAuditFilter((prev) => ({ ...prev, dateTo: event.target.value }))}
              />
            </label>
          </div>

          {isLoadingAuditLogs ? <p>Loading audit logs...</p> : null}

          <div className="table-wrap users-table-wrap">
            <table className="users-table audit-table">
              <thead>
                <tr>
                  <th>Time</th>
                  <th>Action</th>
                  <th>Actor</th>
                  <th>Target</th>
                  <th>IP</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                {auditLogs.map((log) => (
                  <tr key={log.id}>
                    <td>{formatAuditDate(log.created_at)}</td>
                    <td>{log.action}</td>
                    <td>{log.actor?.name || '-'}{log.actor?.email ? ` (${log.actor.email})` : ''}</td>
                    <td>{log.auditable_id ? `${log.auditable_type || ''}#${log.auditable_id}` : '-'}</td>
                    <td>{log.ip_address || '-'}</td>
                    <td>{formatAuditDetails(log.event_data)}</td>
                  </tr>
                ))}
                {!auditLogs.length ? (
                  <tr>
                    <td colSpan="6">No audit logs found for the selected filters.</td>
                  </tr>
                ) : null}
              </tbody>
            </table>
          </div>

          <div className="audit-pagination">
            <span>
              Showing page {auditMeta.current_page} of {auditMeta.last_page} ({auditMeta.total} records)
            </span>
            <div className="users-actions">
              <button
                type="button"
                className="ghost-btn"
                onClick={() => loadAuditLogs(Math.max(1, auditMeta.current_page - 1))}
                disabled={auditMeta.current_page <= 1}
              >
                Previous
              </button>
              <button
                type="button"
                className="ghost-btn"
                onClick={() => loadAuditLogs(Math.min(auditMeta.last_page, auditMeta.current_page + 1))}
                disabled={auditMeta.current_page >= auditMeta.last_page}
              >
                Next
              </button>
            </div>
          </div>
        </article>
      </section>
    )
  }

  function renderLogin() {
    return (
      <div className="login-shell">
        <section className="login-frame reveal-2">
          <article className="login-visual">
            <img src={logo} alt="ZuriTours ERP" className="login-logo" />
            <div className="login-blob" aria-hidden="true">
              <div className="login-phone" />
              <div className="login-gear login-gear-a" />
              <div className="login-gear login-gear-b" />
              <span className="login-dot login-dot-a" />
              <span className="login-dot login-dot-b" />
              <span className="login-dot login-dot-c" />
            </div>
          </article>

          <article className="login-panel">
            <div className="login-panel-content">
              <h1>Welcome To Zuri Admin</h1>
              <p>Sign in with your platform admin credentials to access the control center.</p>

              <form className="login-form" onSubmit={onSubmitLogin}>
                <label>
                  Email
                  <input
                    type="email"
                    autoComplete="email"
                    placeholder="Username"
                    value={loginForm.email}
                    onChange={(event) => setLoginForm((prev) => ({ ...prev, email: event.target.value }))}
                    required
                  />
                </label>

                <label>
                  Password
                  <input
                    type="password"
                    autoComplete="current-password"
                    placeholder="Password"
                    value={loginForm.password}
                    onChange={(event) => setLoginForm((prev) => ({ ...prev, password: event.target.value }))}
                    required
                  />
                </label>

                <div className="login-meta">
                  <label className="remember-me">
                    <input type="checkbox" />
                    <span>Remember me</span>
                  </label>
                  <button type="button" className="login-link">Forgot password</button>
                </div>

                {loginError ? <p className="login-error">{loginError}</p> : null}

                <button type="submit" className="login-submit" disabled={isLoggingIn}>
                  {isLoggingIn ? 'Signing in...' : 'Sign In'}
                </button>
              </form>
            </div>
          </article>
        </section>
      </div>
    )
  }

  if (authState !== 'authenticated') {
    return renderLogin()
  }

  return (
    <div className="app-shell">
      <SidebarMui activePage={activePage} setActivePage={setActivePage} />
      <main className="main app-main">
        <header className="topbar reveal-2">
          <div>
            <h1>
              {activePage === 'platform-users'
                ? 'Platform Users Management'
                : activePage === 'audit-logs'
                  ? 'Audit Logs Report'
                  : activePage === 'plans'
                    ? 'Plans & Pricing'
                    : activePage === 'companies'
                      ? 'Companies'
                      : activePage === 'subscriptions'
                        ? 'Subscriptions'
                        : 'ZuriTours SaaS Admin'}
            </h1>
            <p>Manage tours. Control costs. Maximize profit.</p>
          </div>
          <div className="topbar-actions">
            <button
              type="button"
              className="ghost-btn"
              onClick={() => setIsMenuCollapsed((prev) => !prev)}
            >
              {isMenuCollapsed ? 'Show menu' : 'Collapse menu'}
            </button>
            <button type="button" className="ghost-btn">
              {currentUser?.name ? `${currentUser.name} - ${currentPageTitle}` : currentPageTitle}
            </button>
            <button
              type="button"
              className="primary-btn"
              onClick={
                activePage === 'plans'
                  ? resetPlanForm
                  : activePage === 'platform-users'
                    ? resetForm
                    : activePage === 'companies'
                      ? () => setShowCompanyWizard(true)
                      : undefined
              }
            >
              {activePage === 'plans'
                ? 'New plan'
                : activePage === 'platform-users'
                  ? 'New user'
                  : activePage === 'companies'
                    ? '+ New Company'
                    : 'Add company'}
            </button>
            <button type="button" className="ghost-btn" onClick={onLogout} disabled={isLoggingOut}>
              {isLoggingOut ? 'Logging out...' : 'Logout'}
            </button>
          </div>
        </header>

        {activePage === 'platform-users'
          ? renderPlatformUsersPage()
          : activePage === 'plans'
            ? renderPlansPage()
            : activePage === 'audit-logs'
              ? renderAuditLogsPage()
              : activePage === 'companies'
                ? renderCompaniesPage()
                : activePage === 'subscriptions'
                  ? renderSubscriptionsPage()
                  : renderDashboard()}
      </main>

      {/* Company modals — rendered at root level to escape CSS transform stacking contexts */}
      {showCompanyWizard ? (
        <CompanyWizard
          apiBaseUrl={API_BASE_URL}
          apiToken={apiToken}
          onClose={() => setShowCompanyWizard(false)}
          onCreated={() => {
            setShowCompanyWizard(false)
            void loadCompanies()
          }}
        />
      ) : null}

      {editingCompany ? (
        <CompanyEditModal
          key={editingCompany.id}
          company={editingCompany}
          apiBaseUrl={API_BASE_URL}
          apiToken={apiToken}
          onClose={() => setEditingCompany(null)}
          onUpdated={(updated) => {
            setEditingCompany(null)
            setCompanies((prev) => prev.map((c) => c.id === updated.id ? { ...c, ...updated } : c))
          }}
        />
      ) : null}

      {viewingCompany ? (
        <div className="cem-overlay" role="dialog" aria-modal="true" onClick={(e) => { if (e.target === e.currentTarget) setViewingCompany(null) }}>
          <div className="cem-modal">
            <div className="cem-header">
              <div className="cem-header-logo">
                {viewingCompany.logo_path
                  ? <img src={`${API_BASE_URL.replace('/api', '')}/storage/${viewingCompany.logo_path}`} alt="logo" className="cem-logo-img" />
                  : <div className="cem-logo-placeholder">{(viewingCompany.name || 'C').charAt(0).toUpperCase()}</div>
                }
              </div>
              <div className="cem-header-info">
                <h2>{viewingCompany.name}</h2>
                <p>{viewingCompany.company_code} · {viewingCompany.industry || 'Company'} · {[viewingCompany.city, viewingCompany.country].filter(Boolean).join(', ') || '—'}</p>
              </div>
              <button type="button" className="cem-close" onClick={() => {
                setCompanyUsersFilter('all')
                setViewingCompany(null)
              }}>✕</button>
            </div>
            <div className="cem-body">
              {/* Basic Info — 2-col grid */}
              <div className="cem-section">
                <div className="cem-section-title"><span className="cem-section-icon">🏢</span>Basic Information</div>
                <div className="cem-section-body">
                  <div className="cem-view-grid-2">
                    {[
                      ['Company Name', viewingCompany.name],
                      ['Legal Name', viewingCompany.legal_name],
                      ['Company Code', viewingCompany.company_code],
                      ['Registration No.', viewingCompany.registration_number],
                      ['TIN', viewingCompany.tin],
                      ['VAT Number', viewingCompany.vat_number],
                      ['Industry', viewingCompany.industry],
                      ['Business Type', viewingCompany.business_type],
                      ['Incorporation Date', viewingCompany.incorporation_date ? formatDisplayDate(viewingCompany.incorporation_date) : null],
                      ['Status', viewingCompany.status],
                      ['Subscription', viewingCompany.subscription_status],
                      ['Created', viewingCompany.created_at ? formatDisplayDateTime(viewingCompany.created_at) : null],
                      ['Last Updated', viewingCompany.updated_at ? formatDisplayDateTime(viewingCompany.updated_at) : null],
                    ].map(([label, value]) => (
                      <div key={label} className="cem-view-row">
                        <span className="cem-view-label">{label}</span>
                        <span className={`cem-view-value${!value ? ' muted' : ''}`}>{value || '—'}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* Address */}
              <div className="cem-section">
                <div className="cem-section-title"><span className="cem-section-icon">📍</span>Address</div>
                <div className="cem-section-body">
                  <div className="cem-view-table">
                    {[
                      ['Country', viewingCompany.country],
                      ['Region / State', viewingCompany.region],
                      ['City', viewingCompany.city],
                      ['Postal Code', viewingCompany.postal_code],
                      ['Address Line 1', viewingCompany.address_line_1],
                      ['Address Line 2', viewingCompany.address_line_2],
                      ['Google Maps Link', viewingCompany.google_map_location],
                    ].map(([label, value]) => (
                      <div key={label} className="cem-view-row">
                        <span className="cem-view-label">{label}</span>
                        <span className={`cem-view-value${!value ? ' muted' : ''}`}>{value || '—'}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              {/* Contact */}
              <div className="cem-section">
                <div className="cem-section-title"><span className="cem-section-icon">📞</span>Contact Details</div>
                <div className="cem-section-body">
                  <div className="cem-view-table">
                    {[
                      ['Phone', viewingCompany.phone],
                      ['Alt Phone', viewingCompany.alt_phone],
                      ['Email', viewingCompany.email],
                      ['Website', viewingCompany.website],
                      ['WhatsApp', viewingCompany.whatsapp],
                    ].map(([label, value]) => (
                      <div key={label} className="cem-view-row">
                        <span className="cem-view-label">{label}</span>
                        <span className={`cem-view-value${!value ? ' muted' : ''}`}>{value || '—'}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>

              <div className="cem-section">
                <div className="cem-section-title"><span className="cem-section-icon">👥</span>Registered Users</div>
                <div className="cem-section-body">
                  {Array.isArray(viewingCompany.users) && viewingCompany.users.length ? (
                    <>
                      <div className="cem-user-filter-row">
                        <button
                          type="button"
                          className={`cem-user-filter-btn ${companyUsersFilter === 'all' ? 'active' : ''}`}
                          onClick={() => setCompanyUsersFilter('all')}
                        >
                          All ({viewingCompany.users.length})
                        </button>
                        <button
                          type="button"
                          className={`cem-user-filter-btn ${companyUsersFilter === 'inactive' ? 'active' : ''}`}
                          onClick={() => setCompanyUsersFilter('inactive')}
                        >
                          Inactive ({viewingCompany.users.filter((user) => String(user.status || 'active').toLowerCase() === 'inactive').length})
                        </button>
                        <button
                          type="button"
                          className={`cem-user-filter-btn ${companyUsersFilter === 'active' ? 'active' : ''}`}
                          onClick={() => setCompanyUsersFilter('active')}
                        >
                          Active ({viewingCompany.users.filter((user) => String(user.status || 'active').toLowerCase() === 'active').length})
                        </button>
                      </div>

                    <div className="cem-users-list">
                      {filteredViewingCompanyUsers.map((user) => (
                        <div key={user.id} className="cem-user-card">
                          <div className="cem-user-main">
                            <strong>{user.name || 'Unnamed user'}</strong>
                            <span>{user.email || 'No email'}</span>
                          </div>
                          <div className="cem-user-meta">
                            <span className={`badge ${String(user.status || 'active').toLowerCase()}`}>
                              {user.status || 'active'}
                            </span>
                            <span className="cem-user-role">
                              {(user.roles || []).map((role) => role.name).join(', ') || 'No role assigned'}
                            </span>
                            <span className="cem-user-last-login">
                              {user.last_login_at ? `Last login: ${formatDisplayDateTime(user.last_login_at)}` : 'Never logged in'}
                            </span>
                            <div className="cem-user-actions">
                              <button
                                type="button"
                                className="cem-user-action-btn status-toggle"
                                onClick={() => onToggleCompanyUserStatus(user)}
                              >
                                {String(user.status || 'active').toLowerCase() === 'active' ? 'Deactivate User' : 'Activate User'}
                              </button>
                              <button
                                type="button"
                                className="cem-user-action-btn"
                                onClick={() => onResetCompanyUserPassword(user)}
                                disabled={!user.email || String(user.status || 'active').toLowerCase() !== 'active'}
                                title={user.email ? 'Generate new password and send by email' : 'User has no email address'}
                              >
                                Reset Password
                              </button>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                    </>
                  ) : (
                    <div className="empty-state">
                      <div className="empty-state-icon">👤</div>
                      <h3>No registered users</h3>
                      <p>This company does not have any tenant users assigned yet.</p>
                    </div>
                  )}

                  {Array.isArray(viewingCompany.users) && viewingCompany.users.length > 0 && filteredViewingCompanyUsers.length === 0 ? (
                    <div className="empty-state" style={{ marginTop: '12px' }}>
                      <div className="empty-state-icon">🔎</div>
                      <h3>No users in this filter</h3>
                      <p>Switch filter to view other users.</p>
                    </div>
                  ) : null}
                </div>
              </div>

              {/* Finance + Notifications side by side */}
              <div className="cem-split-grid">
                <div className="cem-section">
                  <div className="cem-section-title"><span className="cem-section-icon">💰</span>Finance</div>
                  <div className="cem-section-body">
                    <div className="cem-view-table">
                      {[
                        ['Currency', viewingCompany.default_currency],
                        ['Year Start', viewingCompany.financial_year_start ? `Month ${viewingCompany.financial_year_start}` : null],
                        ['Multi-Currency', viewingCompany.multi_currency_enabled ? '✅ Enabled' : '❌ Disabled'],
                        ['Tax Module', viewingCompany.tax_enabled ? '✅ Enabled' : '❌ Disabled'],
                      ].map(([label, value]) => (
                        <div key={label} className="cem-view-row">
                          <span className="cem-view-label">{label}</span>
                          <span className={`cem-view-value${!value ? ' muted' : ''}`}>{value || '—'}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>

                <div className="cem-section">
                  <div className="cem-section-title"><span className="cem-section-icon">🔔</span>Notifications</div>
                  <div className="cem-section-body">
                    <div className="cem-view-table">
                      {[
                        ['Email', viewingCompany.notify_email ? '✅ Yes' : '❌ No'],
                        ['WhatsApp', viewingCompany.notify_whatsapp ? '✅ Yes' : '❌ No'],
                        ['SMS', viewingCompany.notify_sms ? '✅ Yes' : '❌ No'],
                        ['Events', Array.isArray(viewingCompany.notify_on) && viewingCompany.notify_on.length
                          ? viewingCompany.notify_on.join(', ')
                          : null],
                      ].map(([label, value]) => (
                        <div key={label} className="cem-view-row">
                          <span className="cem-view-label">{label}</span>
                          <span className={`cem-view-value${!value ? ' muted' : ''}`}>{value || 'None'}</span>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              {/* Branding */}
              <div className="cem-section">
                <div className="cem-section-title"><span className="cem-section-icon">🌿</span>Branding / Logos</div>
                <div className="cem-section-body">
                  <div className="cem-branding-view-grid">
                    {[
                      { label: 'Main Logo', path: viewingCompany.logo_path },
                      { label: 'Email Logo', path: viewingCompany.email_logo_path },
                      { label: 'Document Logo', path: viewingCompany.document_logo_path },
                    ].map(({ label, path }) => (
                      <div key={label} className="cem-branding-view-item">
                        <span className="cem-view-label cem-view-label-center">{label}</span>
                        <div className="cem-logo-preview-box cem-logo-preview-box-full">
                          {path
                            ? <img
                                src={`${API_BASE_URL.replace('/api', '')}/storage/${path}`}
                                alt={label}
                                className="cem-logo-preview-img"
                              />
                            : <span className="cem-logo-empty">No image</span>
                          }
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
            <div className="cem-footer">
              <button type="button" className="ghost-btn" onClick={() => setViewingCompany(null)}>Close</button>
              <button type="button" className="primary-btn" onClick={() => { setViewingCompany(null); openEditCompany(viewingCompany) }}>✏️ Edit Company</button>
            </div>
          </div>
        </div>
      ) : null}

      {showSubscriptionModal ? (
        <SubscriptionModal
          apiBaseUrl={API_BASE_URL}
          apiToken={apiToken}
          companies={companies}
          plans={plans}
          onClose={() => setShowSubscriptionModal(false)}
          onCreated={(sub) => {
            setShowSubscriptionModal(false)
            setSubscriptions((prev) => [sub, ...prev])
          }}
        />
      ) : null}
    </div>
  )
}

export default App
