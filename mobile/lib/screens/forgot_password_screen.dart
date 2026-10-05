import 'package:flutter/material.dart';

import '../services/api_client.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _identifierController = TextEditingController();
  String _channel = 'email';
  bool _loading = false;
  String? _error;

  Future<void> _submit() async {
    final identifier = _identifierController.text.trim();
    if (identifier.isEmpty) {
      setState(() => _error = 'Enter your ${_channel == 'email' ? 'email' : 'phone number'}.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ApiClient().sendPasswordResetOtp(channel: _channel, identifier: identifier);
      if (!mounted) return;
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ResetPasswordScreen(channel: _channel, identifier: identifier),
        ),
      );
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _identifierController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: RadialGradient(
            center: Alignment(0, -0.6),
            radius: 1.1,
            colors: [AppColors.ink2, AppColors.paper2],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 12, 20, 8),
                child: PageHeader(title: 'Forgot Password'),
              ),
              Expanded(
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 360),
                    child: Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'We\'ll send a 6-digit code to verify it\'s you.',
                            style: AppTheme.display(fontSize: 13, fontWeight: FontWeight.w400, color: AppColors.steel),
                          ),
                          const SizedBox(height: 20),
                          SegmentedButton<String>(
                            segments: const [
                              ButtonSegment(value: 'email', label: Text('Email')),
                              ButtonSegment(value: 'phone', label: Text('Phone')),
                            ],
                            selected: {_channel},
                            onSelectionChanged: (selection) {
                              setState(() {
                                _channel = selection.first;
                                _identifierController.clear();
                                _error = null;
                              });
                            },
                          ),
                          const SizedBox(height: 20),
                          TextField(
                            controller: _identifierController,
                            decoration: InputDecoration(
                              labelText: _channel == 'email' ? 'Email' : 'Phone number',
                            ),
                            keyboardType: _channel == 'email' ? TextInputType.emailAddress : TextInputType.phone,
                          ),
                          const SizedBox(height: 24),
                          if (_error != null) ...[
                            Text(_error!, style: AppTheme.body(color: AppColors.tape)),
                            const SizedBox(height: 16),
                          ],
                          FilledButton(
                            onPressed: _loading ? null : _submit,
                            child: _loading
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.voidBg),
                                  )
                                : const Text('SEND CODE'),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class ResetPasswordScreen extends StatefulWidget {
  const ResetPasswordScreen({super.key, required this.channel, required this.identifier});

  final String channel;
  final String identifier;

  @override
  State<ResetPasswordScreen> createState() => _ResetPasswordScreenState();
}

class _ResetPasswordScreenState extends State<ResetPasswordScreen> {
  final _otpController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmController = TextEditingController();
  bool _loading = false;
  bool _resending = false;
  String? _error;

  Future<void> _resend() async {
    setState(() => _resending = true);
    try {
      await ApiClient().sendPasswordResetOtp(channel: widget.channel, identifier: widget.identifier);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Code resent.')));
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  Future<void> _submit() async {
    if (_passwordController.text.length < 8) {
      setState(() => _error = 'Password must be at least 8 characters.');
      return;
    }
    if (_passwordController.text != _confirmController.text) {
      setState(() => _error = 'Passwords do not match.');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ApiClient().resetPassword(
        channel: widget.channel,
        identifier: widget.identifier,
        otp: _otpController.text.trim(),
        password: _passwordController.text,
      );
      if (!mounted) return;
      Navigator.of(context).popUntil((route) => route.isFirst);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Password reset. Please log in.')),
      );
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  void dispose() {
    _otpController.dispose();
    _passwordController.dispose();
    _confirmController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final destination = widget.channel == 'email' ? 'email' : 'phone';

    return Scaffold(
      body: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: RadialGradient(
            center: Alignment(0, -0.6),
            radius: 1.1,
            colors: [AppColors.ink2, AppColors.paper2],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              const Padding(
                padding: EdgeInsets.fromLTRB(20, 12, 20, 8),
                child: PageHeader(title: 'Enter Code'),
              ),
              Expanded(
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 360),
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Enter the 6-digit code sent to your $destination and choose a new password.',
                            style: AppTheme.display(fontSize: 13, fontWeight: FontWeight.w400, color: AppColors.steel),
                          ),
                          const SizedBox(height: 20),
                          TextField(
                            controller: _otpController,
                            decoration: const InputDecoration(labelText: 'Code'),
                            keyboardType: TextInputType.number,
                            maxLength: 6,
                            style: AppTheme.mono(letterSpacing: 4),
                          ),
                          const SizedBox(height: 6),
                          TextField(
                            controller: _passwordController,
                            decoration: const InputDecoration(labelText: 'New password'),
                            obscureText: true,
                          ),
                          const SizedBox(height: 14),
                          TextField(
                            controller: _confirmController,
                            decoration: const InputDecoration(labelText: 'Confirm password'),
                            obscureText: true,
                          ),
                          const SizedBox(height: 8),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: _resending ? null : _resend,
                              child: Text(_resending ? 'RESENDING…' : 'RESEND CODE'),
                            ),
                          ),
                          if (_error != null) ...[
                            Text(_error!, style: AppTheme.body(color: AppColors.tape)),
                            const SizedBox(height: 16),
                          ],
                          FilledButton(
                            onPressed: _loading ? null : _submit,
                            child: _loading
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.voidBg),
                                  )
                                : const Text('RESET PASSWORD'),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
